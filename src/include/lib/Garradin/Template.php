<?php

namespace Garradin;

use KD2\Form;
use Garradin\Membres\Session;
use Garradin\Entities\Accounting\Account;

class Template extends \KD2\Smartyer
{
	static protected $_instance = null;

	static public function getInstance()
	{
		return self::$_instance ?: self::$_instance = new Template;
	}

	private function __clone()
	{
	}

	public function __construct()
	{
		parent::__construct();

		if (!file_exists(CACHE_ROOT . '/compiled'))
		{
			Utils::safe_mkdir(CACHE_ROOT . '/compiled', 0777, true);
		}

		$this->setTemplatesDir(ROOT . '/templates');
		$this->setCompiledDir(CACHE_ROOT . '/compiled');
		$this->setNamespace('Garradin');

		// Hash de la version pour les éléments statiques (cache)
		// On ne peut pas utiliser la version directement comme query string
		// pour les éléments statiques (genre /admin/static/admin.css?v0.9.0)
		// car cela dévoilerait la version de Garradin utilisée, posant un souci
		// en cas de faille, on cache donc la version utilisée, chaque instance
		// aura sa propre version
		$this->assign('version_hash', substr(sha1(garradin_version() . garradin_manifest() . ROOT . SECRET_KEY), 0, 10));

		$this->assign('www_url', WWW_URL);
		$this->assign('self_url', Utils::getSelfURI());
		$this->assign('self_url_no_qs', Utils::getSelfURI(false));

		$this->assign('is_logged', false);

		$this->assign('password_pattern', sprintf('.{%d,}', Session::MINIMUM_PASSWORD_LENGTH));
		$this->assign('password_length', Session::MINIMUM_PASSWORD_LENGTH);

		$this->register_compile_function('continue', function ($pos, $block, $name, $raw_args) {
			if ($block == 'continue')
			{
				return 'continue;';
			}
		});

		$this->register_function('form_errors', [$this, 'formErrors']);
		$this->register_function('show_error', [$this, 'showError']);
		$this->register_function('form_field', [$this, 'formField']);
		$this->register_function('html_champ_membre', [$this, 'formChampMembre']);
		$this->register_function('input', [$this, 'formInput']);

		$this->register_function('custom_colors', [$this, 'customColors']);
		$this->register_function('plugin_url', ['Garradin\Utils', 'plugin_url']);
		$this->register_function('diff', [$this, 'diff']);
		$this->register_function('pagination', [$this, 'pagination']);
		$this->register_function('format_droits', [$this, 'formatDroits']);

		$this->register_function('csrf_field', function ($params) {
			return Form::tokenHTML($params['key']);
		});

		$this->register_function('icon', [$this, 'widgetIcon']);
		$this->register_function('button', [$this, 'widgetButton']);
		$this->register_function('linkbutton', [$this, 'widgetLinkButton']);

		$this->register_modifier('strlen', 'strlen');
		$this->register_modifier('dump', ['KD2\ErrorManager', 'dump']);
		$this->register_modifier('get_country_name', ['Garradin\Utils', 'getCountryName']);
		$this->register_modifier('format_sqlite_date_to_french', ['Garradin\Utils', 'sqliteDateToFrench']);
		$this->register_modifier('format_bytes', ['Garradin\Utils', 'format_bytes']);
		$this->register_modifier('format_tel', [$this, 'formatPhoneNumber']);
		$this->register_modifier('abs', 'abs');
		$this->register_modifier('display_champ_membre', [$this, 'displayChampMembre']);

		$this->register_modifier('date_fr', function ($ts, $format = 'd/m/Y H:i:s') {
			return Utils::date_fr($format, $ts);
		});

		$this->register_modifier('date_short', function ($dt) {
			return Utils::date_fr('d/m/Y', $dt);
		});

		$this->register_modifier('html_money', [$this, 'htmlMoney']);
		$this->register_modifier('money_currency', [$this, 'htmlMoneyCurrency']);

		$this->register_modifier('format_wiki', function ($str) {
			$str = Utils::SkrivToHTML($str);
			$str = Squelette_Filtres::typo_fr($str);
			return $str;
		});

		$this->register_modifier('liens_wiki', function ($str, $prefix) {
			return preg_replace_callback('!<a href="([^/.:@]+)">!i', function ($matches) use ($prefix) {
				return '<a href="' . $prefix . Wiki::transformTitleToURI($matches[1]) . '">';
			}, $str);
		});

	}

	protected function htmlMoney($number, bool $hide_empty = true): string
	{
		if ($hide_empty && !$number) {
			return '';
		}

		return sprintf('<b class="money">%s</b>', Utils::money_format($number, ',', '&nbsp;', $hide_empty));
	}

	protected function htmlMoneyCurrency($number, bool $hide_empty = true): string
	{
		$out = $this->htmlMoney($number, $hide_empty);

		if ($out !== '') {
			$out .= '&nbsp;' . Config::getInstance()->get('monnaie');
		}

		return $out;
	}

	protected function formErrors($params)
	{
		$form = $this->getTemplateVars('form');

		if (!$form->hasErrors())
		{
			return '';
		}

		return '<div class="block error"><ul><li>' . implode('</li><li>', $form->getErrorMessages(!empty($params['membre']) ? true : false)) . '</li></ul></div>';
	}

	protected function showError($params)
	{
		if (!$params['if'])
		{
			return '';
		}

		return '<p class="block error">' . $this->escape($params['message']) . '</p>';
	}

	protected function widgetIcon(array $params): string
	{
		if (empty($params['href'])) {
			return sprintf('<b class="icn">%s</b>', Utils::iconUnicode($params['shape']));
		}

		return sprintf('<a href="%s" class="icn" title="%s">%s</a>', $this->escape(ADMIN_URL . $params['href']), $this->escape($params['label']), Utils::iconUnicode($params['shape']));
	}

	protected function widgetButton(array $params): string
	{
		$icon = Utils::iconUnicode($params['shape']);
		$label = isset($params['label']) ? $this->escape($params['label']) : '';
		unset($params['label'], $params['shape']);

		if (!isset($params['type'])) {
			$params['type'] = 'button';
		}

		$params['class'] = ' icn-btn';

		array_walk($params, function (&$v, $k) {
			$v = sprintf('%s="%s"', $k, $this->escape($v));
		});

		$params = implode(' ', $params);

		return sprintf('<button %s data-icon="%s">%s</button>', $params, $icon, $label);
	}

	protected function widgetLinkButton(array $params): string
	{
		$href = $params['href'];

		if (!preg_match('!^(?:/|https?://)!', $href)) {
			$href = ADMIN_URL . $params['href'];
		}

		return sprintf('<a class="icn-btn" data-icon="%s" href="%s">%s</a>', Utils::iconUnicode($params['shape']), $this->escape($href), $this->escape($params['label']));
	}

	protected function formInput(array $params)
	{
		static $params_list = ['value', 'default', 'type', 'help', 'label', 'name', 'options', 'source'];

		// Extract params and keep attributes separated
		$attributes = array_diff_key($params, array_flip($params_list));
		$params = array_intersect_key($params, array_flip($params_list));
		extract($params, \EXTR_SKIP);

		if (!isset($name, $type)) {
			throw new \InvalidArgumentException('Missing name or type');
		}

		$current_value = null;
		$current_value_from_user = false;

		if (isset($_POST[$name])) {
			$current_value = $_POST[$name];
			$current_value_from_user = true;
		}
		elseif (isset($source) && is_object($source) && isset($source->$name)) {
			$current_value = $source->$name;
		}
		elseif (isset($source) && is_array($source) && isset($source[$name])) {
			$current_value = $source[$name];
		}
		elseif (isset($default)) {
			$current_value = $default;
		}

		if ($type == 'date' && is_object($current_value) && $current_value instanceof \DateTimeInterface) {
			$current_value = $current_value->format('d/m/Y');
		}

		$attributes['id'] = 'f_' . $name;
		$attributes['name'] = $name;

		if ($type == 'radio' || $type == 'checkbox') {
			$attributes['id'] .= '_' . $value;

			if ($current_value == $value) {
				$attributes['checked'] = 'checked';
			}

			$attributes['value'] = $value;
		}
		elseif ($type == 'file') {
			$help = sprintf('Taille maximale : %s', Utils::format_bytes(Utils::getMaxUploadSize()));
		}
		elseif ($type == 'date') {
			$type = 'text';
			$attributes['placeholder'] = 'JJ/MM/AAAA';
			$attributes['data-input'] = 'date';
			$attributes['size'] = 12;
			$attributes['maxlength'] = 10;
			$attributes['pattern'] = '\d\d?/\d\d?/\d{4}';
		}

		// Create attributes string
		if (array_key_exists('required', $attributes)) {
			$attributes['required'] = 'required';
		}

		if (!empty($attributes['disabled'])) {
			$attributes['disabled'] = 'disabled';
			unset($attributes['required']);
		}
		else {
			unset($attributes['disabled']);
		}

		$attributes_string = $attributes;

		array_walk($attributes_string, function (&$v, $k) {
			$v = sprintf('%s="%s"', $k, $v);
		});

		$attributes_string = implode(' ', $attributes_string);

		if ($type == 'select') {
			$input = sprintf('<select %s>', $attributes_string);

			foreach ($options as $_key => $_value) {
				$input .= sprintf('<option value="%s"%s>%s</option>', $_key, $current_value == $_key ? ' selected="selected"' : '', $this->escape($_value));
			}

			$input .= '</select>';
		}
		elseif ($type == 'select_groups') {
			$input = sprintf('<select %s>', $attributes_string);

			foreach ($options as $optgroup => $suboptions) {
				$input .= sprintf('<optgroup label="%s">', $this->escape($optgroup));

				foreach ($suboptions as $_key => $_value) {
					$input .= sprintf('<option value="%s"%s>%s</option>', $_key, $current_value == $_key ? ' selected="selected"' : '', $this->escape($_value));
				}

				$input .= '</optgroup>';
			}

			$input .= '</select>';
		}
		elseif ($type == 'textarea') {
			$input = sprintf('<textarea %s>%s</textarea>', $attributes_string, $this->escape($current_value));
		}
		elseif ($type == 'list') {
			$multiple = !empty($attributes['multiple']);
			$values = '';
			$delete_btn = $this->widgetButton(['shape' => 'delete']);

			if (null !== $current_value) {
				foreach ($current_value as $v => $l) {
					$values .= sprintf('<span class="label"><input type="hidden" name="%s[%s]" value="%s" /> %3$s %s</span>', $this->escape($name), $this->escape($v), $this->escape($l), $multiple ? $delete_btn : '');
				}
			}

			$button = $this->widgetButton([
				'shape' => $multiple ? 'plus' : 'menu',
				'value' => (substr($attributes['target'], 0, 4) === 'http') ? $attributes['target'] : ADMIN_URL . $attributes['target'],
				'label' => $multiple ? 'Ajouter' : 'Sélectionner',
				'data-multiple' => $multiple ? '1' : '0',
				'data-name' => $name,
			]);

			$input = sprintf('<span id="%s_container" class="input-list">%s%s</span>', $this->escape($attributes['id']), $button, $values);
		}
		elseif ($type == 'money') {
			if (null !== $current_value && !$current_value_from_user) {
				$current_value = Utils::money_format($current_value, ',', '');
			}

			$currency = Config::getInstance()->get('monnaie');
			$input = sprintf('<input type="text" pattern="[0-9]*([.,][0-9]{1,2})?" inputmode="decimal" size="8" class="money" %s value="%s" /><b>%s</b>', $attributes_string, $this->escape($current_value), $currency);
		}
		else {
			$value = isset($attributes['value']) ? '' : sprintf(' value="%s"', $this->escape($current_value));
			$input = sprintf('<input type="%s" %s %s />', $type, $attributes_string, $value);
		}

		// No label? then we only want the input without the widget
		if (empty($label)) {
			return $input;
		}

		if ($type == 'file') {
			$input .= sprintf('<input type="hidden" name="MAX_FILE_SIZE" value="%d" id="f_maxsize" />', Utils::return_bytes(Utils::getMaxUploadSize()));
		}

		$label = sprintf('<label for="%s">%s</label>', $attributes['id'], $this->escape($label));

		if ($type == 'radio' || $type == 'checkbox') {
			$out = sprintf('<dd>%s %s', $input, $label);

			if (isset($help)) {
				$out .= sprintf(' <em class="help">(%s)</em>', $this->escape($help));
			}

			$out .= '</dd>';
		}
		else {
			if (array_key_exists('required', $attributes)) {
				$required_label =  ' <b title="Champ obligatoire">(obligatoire)</b>';
			}
			else {
				$required_label =  ' <i>(facultatif)</i>';
			}

			$out = sprintf('<dt>%s%s</dt><dd>%s</dd>', $label, $required_label, $input);

			if (isset($help)) {
				$out .= sprintf('<dd class="help">%s</dd>', $this->escape($help));
			}
		}

		return $out;
	}

	protected function formField(array $params, $escape = true)
	{
		if (!isset($params['name']))
		{
			throw new \BadFunctionCallException('name argument is mandatory');
		}

		$name = $params['name'];

		if (isset($_POST[$name]))
			$value = $_POST[$name];
		elseif (isset($params['data']) && is_array($params['data']) && array_key_exists($name, $params['data']))
		{
			$value = $params['data'][$name];
		}
		elseif (isset($params['data']) && is_object($params['data']) && property_exists($params['data'], $name))
		{
			$value = $params['data']->$name;
		}
		elseif (isset($params['default']))
			$value = $params['default'];
		else
			$value = '';

		if (is_array($value))
		{
			return $value;
		}

		if (isset($params['checked']))
		{
			if ($value == $params['checked'])
				return ' checked="checked" ';

			return '';
		}
		elseif (isset($params['selected']))
		{
			if ($value == $params['selected'])
				return ' selected="selected" ';

			return '';
		}

		return $escape ? htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8') : $value;
	}

	protected function formatPhoneNumber($n)
	{
		$country = Config::getInstance()->get('pays');

		if ($country !== 'FR') {
			return $n;
		}

		if ('FR' === $country && $n[0] === '0' && strlen($n) === 10) {
			$n = preg_replace('!(\d{2})!', '\\1 ', $n);
		}

		return $n;
	}

	protected function customColors()
	{
		$config = Config::getInstance();

		$couleur1 = $config->get('couleur1') ?: ADMIN_COLOR1;
		$couleur2 = $config->get('couleur2') ?: ADMIN_COLOR2;
		$image_fond = ADMIN_BACKGROUND_IMAGE;

		if ($config->get('image_fond'))
		{
			try {
				$f = new Fichiers($config->get('image_fond'));
				$image_fond = $f->getURL();
			}
			catch (\InvalidArgumentException $e)
			{
				// Fichier qui n'existe pas/plus
			}
		}

		// Transformation Hexa vers décimal
		$couleur1 = implode(', ', sscanf($couleur1, '#%02x%02x%02x'));
		$couleur2 = implode(', ', sscanf($couleur2, '#%02x%02x%02x'));

		$out = '
		<style type="text/css">
		:root {
			--gMainColor: %s;
			--gSecondColor: %s;
		}
		@media screen, handheld {
			.header .menu, body {
				background-image: url("%s");
			}
		}
		</style>';

		return sprintf($out, $couleur1, $couleur2, $image_fond);
	}

	protected function displayChampMembre($v, $config = null)
	{
		if (is_string($config)) {
			$config = Config::getInstance()->get('champs_membres')->get($config);
		}

		if (null === $config) {
			return htmlspecialchars($v);
		}

		switch ($config->type)
		{
			case 'checkbox':
				return $v ? 'Oui' : 'Non';
			case 'email':
				return '<a href="mailto:' . rawurlencode($v) . '">' . htmlspecialchars($v) . '</a>';
			case 'tel':
				return '<a href="tel:' . rawurlencode($v) . '">' . htmlspecialchars($v) . '</a>';
			case 'url':
				return '<a href="' . htmlspecialchars($v) . '">' . htmlspecialchars($v) . '</a>';
			case 'country':
				return Utils::getCountryName($v);
			case 'date':
				return Utils::sqliteDateToFrench($v);
			case 'multiple':
				$out = [];

				foreach ($config->options as $b => $name)
				{
					if ($v & (0x01 << $b))
						$out[] = $name;
				}

				return htmlspecialchars(implode(', ', $out));
			default:
				return htmlspecialchars($v);
		}
	}

	protected function formChampMembre($params)
	{
		if (empty($params['config']) || empty($params['name']))
			throw new \BadFunctionCallException('Paramètres type et name obligatoires.');

		$config = $params['config'];
		$type = $config->type;

		if ($params['name'] == 'passe' || (!empty($params['user_mode']) && !empty($config->private)))
		{
			return '';
		}

		$options = [];

		if ($type == 'select' || $type == 'multiple')
		{
			if (empty($config->options))
			{
				throw new \BadFunctionCallException('Paramètre options obligatoire pour champ de type ' . $type);
			}

			$options = (array) $config->options;
		}
		elseif ($type == 'country')
		{
			$type = 'select';
			$options = Utils::getCountryList();
			$params['default'] = Config::getInstance()->get('pays');
		}
		elseif ($type == 'date')
		{
			$params['pattern'] = '\d{4}-\d{2}-\d{2}';
		}

		$field = '';
		$value = $this->formField($params, false);
		$attributes = 'name="' . htmlspecialchars($params['name'], ENT_QUOTES, 'UTF-8') . '" ';
		$attributes .= 'id="f_' . htmlspecialchars($params['name'], ENT_QUOTES, 'UTF-8') . '" ';

		if ($params['name'] == 'numero' && $config->type == 'number' && !$value)
		{
			$value = DB::getInstance()->firstColumn('SELECT MAX(numero) + 1 FROM membres;');
		}

		if (!empty($params['disabled']))
		{
			$attributes .= 'disabled="disabled" ';
		}

		if (!empty($config->mandatory))
		{
			$attributes .= 'required="required" ';
		}

		$attributes .= 'autocomplete="off" ';

		if (!empty($params['user_mode']) && empty($config->editable))
		{
			$out = '<dt>' . htmlspecialchars($config->title, ENT_QUOTES, 'UTF-8') . '</dt>';
			$out .= '<dd>' . (trim($value) === '' ? 'Non renseigné' : $this->displayChampMembre($value, $config)) . '</dd>';
			return $out;
		}

		if ($type == 'select')
		{
			$field .= '<select '.$attributes.'>';
			foreach ($options as $k=>$v)
			{
				if (is_int($k))
					$k = $v;

				$field .= '<option value="' . htmlspecialchars($k, ENT_QUOTES, 'UTF-8') . '"';

				if ($value == $k || empty($value) && !empty($params['default']))
					$field .= ' selected="selected"';

				$field .= '>' . htmlspecialchars($v, ENT_QUOTES, 'UTF-8') . '</option>';
			}
			$field .= '</select>';
		}
		elseif ($type == 'multiple')
		{
			if (is_array($value))
			{
				$binary = 0;

				foreach ($value as $k => $v)
				{
					if (array_key_exists($k, $options) && !empty($v))
					{
						$binary |= 0x01 << $k;
					}
				}

				$value = $binary;
			}

			// Forcer la valeur à être un entier (depuis PHP 7.1)
			$value = (int)$value;

			foreach ($options as $k=>$v)
			{
				$b = 0x01 << (int)$k;
				$field .= '<label><input type="checkbox" name="' 
					. htmlspecialchars($params['name'], ENT_QUOTES, 'UTF-8') . '[' . (int)$k . ']" value="1" '
					. (($value & $b) ? 'checked="checked"' : '') . ' ' . $attributes . '/> ' 
					. htmlspecialchars($v, ENT_QUOTES, 'UTF-8') . '</label><br />';
			}
		}
		elseif ($type == 'textarea')
		{
			$field .= '<textarea ' . $attributes . 'cols="30" rows="5">' . htmlspecialchars($value, ENT_QUOTES) . '</textarea>';
		}
		else
		{
			if ($type == 'checkbox')
			{
				if (!empty($value))
				{
					$attributes .= 'checked="checked" ';
				}

				$value = '1';
			}

			$field .= '<input type="' . $type . '" ' . $attributes . ' value="' . htmlspecialchars($value, ENT_QUOTES) . '" />';
		}

		$out = '
		<dt>';

		if ($type == 'checkbox')
		{
			$out .= $field . ' ';
		}

		$out .= '<label for="f_' . htmlspecialchars($params['name'], ENT_QUOTES, 'UTF-8') . '">'
			. htmlspecialchars($config->title, ENT_QUOTES, 'UTF-8') . '</label>';

		if (!empty($config->mandatory))
		{
			$out .= ' <b title="(Champ obligatoire)">obligatoire</b>';
		}

		$out .= '</dt>';

		if (!empty($config->help))
		{
			$out .= '
		<dd class="help">' . htmlspecialchars($config->help, ENT_QUOTES, 'UTF-8') . '</dd>';
		}

		if ($type != 'checkbox')
		{
			$out .= '
		<dd>' . $field . '</dd>';
		}

		return $out;
	}

	protected function diff(array $params)
	{
		if (!isset($params['old']) || !isset($params['new']))
		{
			throw new \BadFunctionCallException('Paramètres old et new requis.');
		}

		$old = $params['old'];
		$new = $params['new'];

		$diff = \KD2\SimpleDiff::diff_to_array(false, $old, $new, 3);

		$out = '<table class="diff">';
		$prev = key($diff);

		foreach ($diff as $i=>$line)
		{
			if ($i > $prev + 1)
			{
				$out .= '<tr><td colspan="5" class="separator"><hr /></td></tr>';
			}

			list($type, $old, $new) = $line;

			$class1 = $class2 = '';
			$t1 = $t2 = '';

			if ($type == \KD2\SimpleDiff::INS)
			{
				$class2 = 'ins';
				$t2 = '<b class="icn">➕</b>';
				$old = htmlspecialchars($old, ENT_QUOTES, 'UTF-8');
				$new = htmlspecialchars($new, ENT_QUOTES, 'UTF-8');
			}
			elseif ($type == \KD2\SimpleDiff::DEL)
			{
				$class1 = 'del';
				$t1 = '<b class="icn">➖</b>';
				$old = htmlspecialchars($old, ENT_QUOTES, 'UTF-8');
				$new = htmlspecialchars($new, ENT_QUOTES, 'UTF-8');
			}
			elseif ($type == \KD2\SimpleDiff::CHANGED)
			{
				$class1 = 'del';
				$class2 = 'ins';
				$t1 = '<b class="icn">➖</b>';
				$t2 = '<b class="icn">➕</b>';

				$lineDiff = \KD2\SimpleDiff::wdiff($old, $new);
				$lineDiff = htmlspecialchars($lineDiff, ENT_QUOTES, 'UTF-8');

				// Don't show new things in deleted line
				$old = preg_replace('!\{\+(?:.*)\+\}!U', '', $lineDiff);
				$old = str_replace('  ', ' ', $old);
				$old = str_replace('-] [-', ' ', $old);
				$old = preg_replace('!\[-(.*)-\]!U', '<del>\\1</del>', $old);

				// Don't show old things in added line
				$new = preg_replace('!\[-(?:.*)-\]!U', '', $lineDiff);
				$new = str_replace('  ', ' ', $new);
				$new = str_replace('+} {+', ' ', $new);
				$new = preg_replace('!\{\+(.*)\+\}!U', '<ins>\\1</ins>', $new);
			}
			else
			{
				$old = htmlspecialchars($old, ENT_QUOTES, 'UTF-8');
				$new = htmlspecialchars($new, ENT_QUOTES, 'UTF-8');
			}

			$out .= '<tr>';
			$out .= '<td class="line">'.($i+1).'</td>';
			$out .= '<td class="leftChange">'.$t1.'</td>';
			$out .= '<td class="leftText '.$class1.'">'.$old.'</td>';
			$out .= '<td class="rightChange">'.$t2.'</td>';
			$out .= '<td class="rightText '.$class2.'">'.$new.'</td>';
			$out .= '</tr>';

			$prev = $i;
		}

		$out .= '</table>';
		return $out;
	}

	protected function pagination(array $params)
	{
		if (!isset($params['url']) || !isset($params['page']) || !isset($params['bypage']) || !isset($params['total']))
			throw new \BadFunctionCallException("Paramètre manquant pour pagination");

		if ($params['total'] == -1)
			return '';

		$pagination = Utils::getGenericPagination($params['page'], $params['total'], $params['bypage']);

		if (empty($pagination))
			return '';

		$out = '<ul class="pagination">';
		$encoded_url = rawurlencode('[ID]');

		foreach ($pagination as &$page)
		{
			$attributes = '';

			if (!empty($page['class']))
				$attributes .= ' class="' . htmlspecialchars($page['class']) . '" ';

			$out .= '<li'.$attributes.'>';

			$attributes = '';

			if (!empty($page['accesskey']))
				$attributes .= ' accesskey="' . htmlspecialchars($page['accesskey']) . '" ';

			$out .= '<a' . $attributes . ' href="' . str_replace(['[ID]', $encoded_url], htmlspecialchars($page['id']), $params['url']) . '">';
			$out .= htmlspecialchars($page['label']);
			$out .= '</a>';
			$out .= '</li>' . "\n";
		}

		$out .= '</ul>';

		return $out;
	}

	protected function formatDroits(array $params)
	{
		$droits = $params['droits'];

		$out = ['connexion' => '', 'inscription' => '', 'membres' => '', 'compta' => '',
			'wiki' => '', 'config' => ''];
		$classes = [
			Membres::DROIT_AUCUN   =>  'aucun',
			Membres::DROIT_ACCES   =>  'acces',
			Membres::DROIT_ECRITURE=>  'ecriture',
			Membres::DROIT_ADMIN   =>  'admin',
		];

		foreach ($droits as $cle=>$droit)
		{
			$cle = str_replace('droit_', '', $cle);

			if (array_key_exists($cle, $out))
			{

				$class = $classes[$droit];
				$desc = false;
				$s = false;

				if ($cle == 'connexion')
				{
					if ($droit == Membres::DROIT_AUCUN)
						$desc = 'N\'a pas le droit de se connecter';
					else
						$desc = 'A le droit de se connecter';
				}
				elseif ($cle == 'inscription')
				{
					if ($droit == Membres::DROIT_AUCUN)
						$desc = 'N\'a pas le droit de s\'inscrire seul';
					else
						$desc = 'A le droit de s\'inscrire seul';
				}
				elseif ($cle == 'config')
				{
					$s = '&#x2611;';

					if ($droit == Membres::DROIT_AUCUN)
						$desc = 'Ne peut modifier la configuration';
					else
						$desc = 'Peut modifier la configuration';
				}
				elseif ($cle == 'compta')
				{
					$s = '&euro;';
				}

				if (!$s)
					$s = strtoupper($cle[0]);

				if (!$desc)
				{
					$desc = ucfirst($cle). ' : ';

					if ($droit == Membres::DROIT_AUCUN)
						$desc .= 'Pas accès';
					elseif ($droit == Membres::DROIT_ACCES)
						$desc .= 'Lecture uniquement';
					elseif ($droit == Membres::DROIT_ECRITURE)
						$desc .= 'Lecture & écriture';
					else
						$desc .= 'Administration';
				}

				$out[$cle] = '<b class="'.$class.' '.$cle.'" title="'
					.htmlspecialchars($desc, ENT_QUOTES, 'UTF-8').'">'.$s.'</b>';
			}
		}

		return implode(' ', $out);
	}
}
