<?php

namespace Garradin;

use KD2\Form;
use KD2\HTTP;
use KD2\Smartyer;
use KD2\Translate;

use Garradin\Membres\Session;
use Garradin\Entities\Accounting\Account;
use Garradin\Entities\Users\Category;
use Garradin\UserTemplate\CommonModifiers;
use Garradin\Web\Render\Skriv;
use Garradin\Files\Files;

class Template extends Smartyer
{
	static protected $_instance = null;

	static public function getInstance()
	{
		return self::$_instance ?: self::$_instance = new Template;
	}

	public function display($template = null)
	{
		$session = Session::getInstance();
		$this->assign('table_export', false);
		$this->assign('pdf_export', false);

		if ($session->isLogged(true)) {
			if (isset($_GET['_pdf'])) {
				$this->assign('pdf_export', true);
				return $this->PDF($template);
			}
			elseif (isset($_GET['_export']) && $_GET['_export'] == 'test') {
				$this->assign('table_export', true);
			}
			elseif (isset($_GET['_export'])) {
				$this->assign('table_export', true);
				$html = $this->fetch($template);

				if (!stripos($html, '<table')) {
					throw new UserException('Nothing to export: no table found');
				}

				$title = 'Export';

				if (preg_match('!<title>([^<]+)</title>!', $html, $match)) {
					$title = html_entity_decode(trim($match[1]));
				}

				return CSV::exportHTML($_GET['_export'], $html, $title);
			}
		}

		return parent::display($template);
	}

	public function PDF(?string $template = null, ?string $title = null)
	{
		$out = $this->fetch($template);

		if (!$title && preg_match('!<title>(.*)</title>!U', $out, $match)) {
			$title = trim($match[1]);
		}

		header('Content-type: application/pdf');
		header(sprintf('Content-Disposition: attachment; filename="%s.pdf"', Utils::safeFileName($title ?: 'Page')));
		Utils::streamPDF($out);
		return $this;
	}

	private function __clone()
	{
	}

	public function __construct($template = null, $parent = null)
	{
		parent::__construct($template, $parent);

		if (null === $parent) {
			if (self::$_instance !== null) {
				throw new \LogicException('Instance already exists');
			}
		}
		else {
			return $this;
		}

		Translate::extendSmartyer($this);

		$cache_dir = SMARTYER_CACHE_ROOT;

		if (!file_exists($cache_dir)) {
			Utils::safe_mkdir($cache_dir, 0777, true);
		}

		$this->setTemplatesDir(ROOT . '/templates');
		$this->setCompiledDir($cache_dir);
		$this->setNamespace('Garradin');


		$this->assign('version_hash', Utils::getVersionHash());

		$this->assign('www_url', WWW_URL);
		$this->assign('admin_url', ADMIN_URL);
		$this->assign('help_pattern_url', HELP_PATTERN_URL);
		$this->assign('help_url', sprintf(HELP_URL, str_replace('/admin/', '', Utils::getSelfURI(false))));
		$this->assign('self_url', Utils::getSelfURI());
		$this->assign('self_url_no_qs', Utils::getSelfURI(false));

		$this->assign('is_logged', false);
		$this->assign('dialog', isset($_GET['_dialog']) ? ($_GET['_dialog'] ?: true) : false);

		$this->assign('password_pattern', sprintf('.{%d,}', Session::MINIMUM_PASSWORD_LENGTH));
		$this->assign('password_length', Session::MINIMUM_PASSWORD_LENGTH);

		$this->register_compile_function('continue', function (Smartyer $s, $pos, $block, $name, $raw_args) {
			if ($block == 'continue')
			{
				return 'continue;';
			}
		});

		$this->register_compile_function('use', function (Smartyer $s, $pos, $block, $name, $raw_args) {
			if ($name == 'use')
			{
				return sprintf('use %s;', $raw_args);
			}
		});

		$this->register_function('form_errors', [$this, 'formErrors']);
		$this->register_function('show_error', [$this, 'showError']);
		$this->register_function('form_field', [$this, 'formField']);
		$this->register_function('html_champ_membre', [$this, 'formChampMembre']);
		$this->register_function('input', [$this, 'formInput']);
		$this->register_function('password_change', [$this, 'passwordChangeInput']);

		$this->register_function('custom_colors', [$this, 'customColors']);
		$this->register_function('plugin_url', ['Garradin\Utils', 'plugin_url']);
		$this->register_function('diff', [$this, 'diff']);
		$this->register_function('display_permissions', [$this, 'displayPermissions']);

		$this->register_function('csrf_field', function ($params) {
			return Form::tokenHTML($params['key']);
		});

		$this->register_function('icon', [$this, 'widgetIcon']);
		$this->register_function('button', [$this, 'widgetButton']);
		$this->register_function('link', [$this, 'widgetLink']);
		$this->register_function('linkbutton', [$this, 'widgetLinkButton']);
		$this->register_function('exportmenu', [$this, 'widgetExportMenu']);

		$this->register_modifier('strlen', fn($a) => strlen($a ?? ''));
		$this->register_modifier('dump', ['KD2\ErrorManager', 'dump']);
		$this->register_modifier('get_country_name', ['Garradin\Utils', 'getCountryName']);
		$this->register_modifier('format_tel', [$this, 'formatPhoneNumber']);
		$this->register_modifier('abs', function($a) { return abs($a ?? 0); });
		$this->register_modifier('display_champ_membre', [$this, 'displayChampMembre']);

		$this->register_modifier('linkify_transactions', function ($str) {
			$str = preg_replace_callback('/(?<=^|\s)(https?:\/\/.*?)(?=\s|$)/', function ($m) {
				return sprintf('<a href="%s" target="_blank">%1$s</a>', htmlspecialchars($m[1]));
			}, $str);

			return preg_replace_callback('/(?<=^|\s)#(\d+)(?=\s|$)/', function ($m) {
				return sprintf('<a href="%s%d">#%2$d</a>',
					Utils::getLocalURL('!acc/transactions/details.php?id='),
					$m[1]
				);
			}, $str);
		});

		$this->register_modifier('format_skriv', function ($str) {
			$skriv = new Skriv;
			return $skriv->render((string) $str);
		});

		foreach (CommonModifiers::MODIFIERS_LIST as $key => $name) {
			$this->register_modifier(is_int($key) ? $name : $key, is_int($key) ? [CommonModifiers::class, $name] : $name);
		}

		foreach (CommonModifiers::FUNCTIONS_LIST as $key => $name) {
			$this->register_function(is_int($key) ? $name : $key, is_int($key) ? [CommonModifiers::class, $name] : $name);
		}

		$this->register_modifier('local_url', [Utils::class, 'getLocalURL']);
	}


	protected function formErrors($params)
	{
		$form = $this->getTemplateVars('form');

		if (!$form->hasErrors())
		{
			return '';
		}

		$errors = $form->getErrorMessages(!empty($params['membre']) ? true : false);

		foreach ($errors as &$error) {
			if ($error instanceof UserException) {
				if ($html = $error->getHTMLMessage()) {
					$message = $html;
				}
				else {
					$message = nl2br($this->escape($error->getMessage()));
				}

				if ($error->hasDetails()) {
					$message = '<h3>' . $message . '</h3>' . $error->getDetailsHTML();
				}

				$error = $message;
			}
			else {
				$error = nl2br($this->escape($error));
			}
		}

		return '<div class="block error"><ul><li>' . implode('</li><li>', $errors) . '</li></ul></div>';
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
		if (isset($params['html']) && $params['html'] == false) {
			return Utils::iconUnicode($params['shape']);
		}

		$attributes = array_diff_key($params, ['shape']);
		$attributes = array_map(fn($v, $k) => sprintf('%s="%s"', $k, $this->escape($v)),
			$attributes, array_keys($attributes));

		$attributes = implode(' ', $attributes);

		return sprintf('<b class="icn" %s>%s</b>', $attributes, Utils::iconUnicode($params['shape']));
	}

	protected function widgetLink(array $params): string
	{
		$href = $params['href'];
		$label = $params['label'];

		// href can be prefixed with '!' to make the URL relative to ADMIN_URL
		if (substr($href, 0, 1) == '!') {
			$href = ADMIN_URL . substr($params['href'], 1);
		}

		// propagate _dialog param if we are in an iframe
		if (isset($_GET['_dialog']) && !isset($params['target'])) {
			$href .= (strpos($href, '?') === false ? '?' : '&') . '_dialog';

			if (!empty($_GET['_dialog'])) {
				$href .= '=' . rawurlencode($_GET['_dialog']);
			}
		}

		if (!isset($params['class'])) {
			$params['class'] = '';
		}

		unset($params['href'], $params['label']);

		array_walk($params, function (&$v, $k) {
			$v = sprintf('%s="%s"', $k, $this->escape($v));
		});

		$params = implode(' ', $params);

		return sprintf('<a href="%s" %s>%s</a>', $this->escape($href), $params, $this->escape($label));
	}

	protected function widgetButton(array $params): string
	{
		$icon = Utils::iconUnicode($params['shape']);
		$label = isset($params['label']) ? $this->escape($params['label']) : '';
		unset($params['label'], $params['shape']);

		if (!isset($params['type'])) {
			$params['type'] = 'button';
		}

		if (!isset($params['class'])) {
			$params['class'] = '';
		}

		if (isset($params['name']) && !isset($params['value'])) {
			$params['value'] = 1;
		}

		$params['class'] .= ' icn-btn';

		// Remove NULL params
		$params = array_filter($params);

		array_walk($params, function (&$v, $k) {
			$v = sprintf('%s="%s"', $k, $this->escape($v));
		});

		$params = implode(' ', $params);

		return sprintf('<button %s data-icon="%s">%s</button>', $params, $icon, $label);
	}

	protected function widgetLinkButton(array $params): string
	{
		if (!empty($params['shape'])) {
			$params['data-icon'] = Utils::iconUnicode($params['shape']);
			unset($params['shape']);
		}

		if (!isset($params['class'])) {
			$params['class'] = '';
		}

		$params['class'] .= ' icn-btn';

		return $this->widgetLink($params);
	}

	protected function widgetExportMenu(array $params): string
	{
		$url = $params['href'] ?? Utils::getSelfURL();
		$suffix = $params['suffix'] ?? 'export=';

		if (false !== strpos($url, '?')) {
			$url .= '&';
		}
		else {
			$url .= '?';
		}

		$url .= $suffix;

		$xlsx = $params['xlsx'] ?? null;

		if (null === $xlsx) {
			$xlsx = !empty(CALC_CONVERT_COMMAND);
		}

		return sprintf('
			<span class="menu-btn %s">
				<b data-icon="↷" class="btn">Export</b>
				<span>%s %s %s</span>
			</span>',
			htmlspecialchars($params['class'] ?? ''),
			$this->widgetLinkButton(['href' => $url . 'csv', 'label' => 'Export CSV', 'shape' => 'export']),
			$this->widgetLinkButton(['href' => $url . 'ods', 'label' => 'Export LibreOffice', 'shape' => 'export']),
			$xlsx ? $this->widgetLinkButton(['href' => $url . 'xlsx', 'label' => 'Export Excel', 'shape' => 'export']) : ''
		);
	}

	protected function passwordChangeInput(array $params)
	{
		$out = $this->formInput(array_merge($params, [
			'type' => 'password',
			'help' => sprintf('(Minimum %d caractères)', Session::MINIMUM_PASSWORD_LENGTH),
			'minlength' => Session::MINIMUM_PASSWORD_LENGTH,
		]));

		$out.= '<dd class="help">Astuce : un mot de passe de quatre mots choisis au hasard dans le dictionnaire est plus sûr et plus simple à retenir qu\'un mot de passe composé de 10 lettres et chiffres.</dd>';

		$suggestion = Utils::suggestPassword();

		$out .= sprintf('<dd class="help">Pas d\'idée&nbsp;? Voici une suggestion choisie au hasard&nbsp;:
                <input type="text" readonly="readonly" title="Cliquer pour utiliser cette suggestion comme mot de passe" id="f_%s_suggest" value="%s" autocomplete="off" size="%d" /></dd>', $params['name'], $suggestion, strlen($suggestion));

		$out .= $this->formInput([
			'type' => 'password',
			'label' => 'Répéter le mot de passe',
			'required' => true,
			'name' => $params['name'] . '_confirm',
			'minlength' => Session::MINIMUM_PASSWORD_LENGTH,
		]);

		return $out;
	}

	protected function formInput(array $params)
	{
		static $params_list = ['value', 'default', 'type', 'help', 'label', 'name', 'options', 'source', 'no_size_limit', 'copy'];

		// Extract params and keep attributes separated
		$attributes = array_diff_key($params, array_flip($params_list));
		$params = array_intersect_key($params, array_flip($params_list));
		extract($params, \EXTR_SKIP);

		if (!isset($name, $type)) {
			throw new \InvalidArgumentException('Missing name or type');
		}

		$suffix = null;

		if ($type == 'datetime') {
			$type = 'date';
			$tparams = func_get_arg(0);
			$tparams['type'] = 'time';
			$tparams['name'] = sprintf('%s_time', $name);
			unset($tparams['label']);
			$suffix = self::formInput($tparams);
		}

		if ($type == 'file' && isset($attributes['accept']) && $attributes['accept'] == 'csv') {
			if (CALC_CONVERT_COMMAND) {
				$help = ($help ?? '') . PHP_EOL . 'Formats acceptés : CSV, LibreOffice Calc (ODS), ou Excel (XLSX)';
				$attributes['accept'] = '.ods,application/vnd.oasis.opendocument.spreadsheet,.xls,application/vnd.ms-excel,.xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,.csv,text/csv,application/csv';
			}
			else {
				$help = ($help ?? '') . PHP_EOL . 'Format accepté : CSV';
				$attributes['accept'] = '.csv,text/csv,application/csv';
			}
		}

		$current_value = null;
		$current_value_from_user = false;

		if (isset($_POST[$name])) {
			$current_value = $_POST[$name];
			$current_value_from_user = true;
		}
		elseif (isset($source) && is_object($source) && isset($source->$name) && !is_null($source->$name)) {
			$current_value = $source->$name;
		}
		elseif (isset($source) && is_array($source) && isset($source[$name])) {
			$current_value = $source[$name];
		}
		elseif (isset($default) && ($type != 'checkbox' || empty($_POST))) {
			$current_value = $default;
		}

		if ($type == 'date' && is_object($current_value) && $current_value instanceof \DateTimeInterface) {
			$current_value = $current_value->format('d/m/Y');
		}
		elseif ($type == 'time' && is_object($current_value) && $current_value instanceof \DateTimeInterface) {
			$current_value = $current_value->format('H:i');
		}
		elseif ($type == 'date' && is_string($current_value)) {
			if ($v = \DateTime::createFromFormat('!Y-m-d', $current_value)) {
				$current_value = $v->format('d/m/Y');
			}
			elseif ($v = \DateTime::createFromFormat('!Y-m-d H:i:s', $current_value)) {
				$current_value = $v->format('d/m/Y');
			}
			elseif ($v = \DateTime::createFromFormat('!Y-m-d H:i', $current_value)) {
				$current_value = $v->format('d/m/Y');
			}
		}
		elseif ($type == 'time' && is_string($current_value)) {
			if ($v = \DateTime::createFromFormat('!Y-m-d H:i:s', $current_value)) {
				$current_value = $v->format('H:i');
			}
			elseif ($v = \DateTime::createFromFormat('!Y-m-d H:i', $current_value)) {
				$current_value = $v->format('H:i');
			}
		}


		$attributes['id'] = 'f_' . str_replace(['[', ']'], '', $name);
		$attributes['name'] = $name;

		if (!isset($attributes['autocomplete']) && ($type == 'money' || $type == 'password')) {
			$attributes['autocomplete'] = 'off';
		}

		if ($type == 'radio' || $type == 'checkbox') {
			$attributes['id'] .= '_' . $value;

			if ($current_value == $value && $current_value !== null) {
				$attributes['checked'] = 'checked';
			}

			$attributes['value'] = $value;
		}
		elseif ($type == 'date') {
			$type = 'text';
			$attributes['placeholder'] = 'JJ/MM/AAAA';
			$attributes['data-input'] = 'date';
			$attributes['size'] = 12;
			$attributes['maxlength'] = 10;
			$attributes['pattern'] = '\d\d?/\d\d?/\d{4}';
		}
		elseif ($type == 'time') {
			$type = 'text';
			$attributes['placeholder'] = 'HH:MM';
			$attributes['data-input'] = 'time';
			$attributes['size'] = 8;
			$attributes['maxlength'] = 5;
			$attributes['pattern'] = '\d\d?:\d\d?';
		}

		// Create attributes string
		if (!empty($attributes['required'])) {
			$attributes['required'] = 'required';
		}
		else {
			unset($attributes['required']);
		}

		if (!empty($attributes['disabled'])) {
			$attributes['disabled'] = 'disabled';
			unset($attributes['required']);
		}
		else {
			unset($attributes['disabled']);
		}

		if (!empty($attributes['readonly'])) {
			$attributes['readonly'] = 'readonly';
		}
		else {
			unset($attributes['readonly']);
		}

		if (array_key_exists('required', $attributes)) {
			$required_label =  ' <b title="Champ obligatoire">(obligatoire)</b>';
		}
		else {
			$required_label =  ' <i>(facultatif)</i>';
		}

		$attributes_string = $attributes;

		array_walk($attributes_string, function (&$v, $k) {
			$v = sprintf('%s="%s"', $k, $v);
		});

		$attributes_string = implode(' ', $attributes_string);

		if ($type == 'radio-btn') {
			$radio = self::formInput(array_merge($params, ['type' => 'radio', 'label' => null, 'help' => null]));
			$out = sprintf('<dd class="radio-btn">%s
				<label for="f_%s_%s"><div><h3>%s</h3>%s</div></label>
			</dd>', $radio, htmlspecialchars($name), htmlspecialchars($value), htmlspecialchars($label), isset($params['help']) ? '<p class="help">' . htmlspecialchars($params['help']) . '</p>' : '');
			return $out;
		}
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

			if (null !== $current_value && is_iterable($current_value)) {
				foreach ($current_value as $v => $l) {
					$values .= sprintf('<span class="label"><input type="hidden" name="%s[%s]" value="%s" /> %3$s %s</span>', $this->escape($name), $this->escape($v), $this->escape($l), $multiple ? $delete_btn : '');
				}
			}

			$button = $this->widgetButton([
				'shape' => $multiple ? 'plus' : 'menu',
				'label' => $multiple ? 'Ajouter' : 'Sélectionner',
				'required' => $attributes['required'] ?? null,
				'value' => Utils::getLocalURL($attributes['target']),
				'data-multiple' => $multiple ? '1' : '0',
				'data-name' => $name,
			]);

			$input = sprintf('<span id="%s_container" class="input-list">%s%s</span>', $this->escape($attributes['id']), $button, $values);
		}
		elseif ($type == 'money') {
			if (null !== $current_value && !$current_value_from_user) {
				$current_value = Utils::money_format($current_value, ',', '');
			}

			if ((string) $current_value === '0') {
				$current_value = '';
			}

			$currency = Config::getInstance()->get('monnaie');
			$input = sprintf('<nobr><input type="text" pattern="-?[0-9]+([.,][0-9]{1,2})?" inputmode="decimal" size="8" class="money" %s value="%s" /><b>%s</b></nobr>', $attributes_string, $this->escape($current_value), $currency);
		}
		else {
			$value = isset($attributes['value']) ? '' : sprintf(' value="%s"', $this->escape($current_value));
			$input = sprintf('<input type="%s" %s %s />', $type, $attributes_string, $value);
		}

		if ($type == 'file') {
			$input .= sprintf('<input type="hidden" name="MAX_FILE_SIZE" value="%d" id="f_maxsize" />', Utils::return_bytes(Utils::getMaxUploadSize()));
		}

		$input .= $suffix;

		// No label? then we only want the input without the widget
		if (empty($label)) {
			if (!array_key_exists('label', $params) && ($type == 'radio' || $type == 'checkbox')) {
				$input .= sprintf('<label for="%s"></label>', $attributes['id']);
			}

			return $input;
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
			if (!empty($copy)) {
				$input .= sprintf('<input type="button" onclick="var a = $(\'#f_%s\'); a.focus(); a.select(); document.execCommand(\'copy\'); this.value = \'Copié !\'; this.focus(); return false;" onblur="this.value = \'Copier\';" value="Copier" />', $params['name']);
			}

			$out = sprintf('<dt>%s%s</dt><dd>%s</dd>', $label, $required_label, $input);

			if ($type == 'file' && empty($params['no_size_limit'])) {
				$out .= sprintf('<dd class="help"><small>Taille maximale : %s</small></dd>', Utils::format_bytes(Utils::getMaxUploadSize()));
			}

			if (isset($help)) {
				$out .= sprintf('<dd class="help">%s</dd>', $this->escape($help));
			}
		}

		return $out;
	}

	/**
	 * @deprecated
	 */
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
		if (empty($n)) {
			return '';
		}

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

		$c1 = ADMIN_COLOR1;
		$c2 = ADMIN_COLOR2;
		$bg = ADMIN_BACKGROUND_IMAGE;

		if (!FORCE_CUSTOM_COLORS) {
			$c1 = $config->get('couleur1') ?: $c1;
			$c2 = $config->get('couleur2') ?: $c2;

			if ($url = $config->fileURL('admin_background')) {
				$bg = $url;
			}
		}

		$out = '
		<style type="text/css">
		:root {
			--gMainColor: %s;
			--gSecondColor: %s;
			--gBgImage: url("%s");
		}
		</style>';

		if ($url = $config->fileURL('admin_css')) {
			$out .= "\n" . sprintf('<link rel="stylesheet" type="text/css" href="%s" />', $url);
		}

		return sprintf($out, CommonModifiers::css_hex_to_rgb($c1), CommonModifiers::css_hex_to_rgb($c2), $bg);
	}

	protected function displayChampMembre($v, $config = null)
	{
		if (is_string($config)) {
			$config = Config::getInstance()->get('champs_membres')->get($config);
		}

		if (null === $config) {
			return htmlspecialchars((string)$v);
		}

		if ($config->type == 'checkbox') {
			return $v ? 'Oui' : 'Non';
		}

		if (empty($v)) {
			return '';
		}

		switch ($config->type)
		{
			case 'password':
				return '*****';
			case 'email':
				return '<a href="mailto:' . rawurlencode($v) . '">' . htmlspecialchars($v) . '</a>';
			case 'tel':
				return '<a href="tel:' . rawurlencode($v) . '">' . htmlspecialchars($this->formatPhoneNumber($v)) . '</a>';
			case 'url':
				return '<a href="' . htmlspecialchars($v) . '" target="_blank">' . htmlspecialchars($v) . '</a>';
			case 'country':
				return Utils::getCountryName($v);
			case 'date':
				return Utils::date_fr($v, 'd/m/Y');
			case 'datetime':
				return Utils::date_fr($v, 'd/m/Y à H:i');
			case 'number':
				return str_replace('.', ',', htmlspecialchars($v));
			case 'multiple':
				// Useful for search results, if a value is not a number
				if (!is_numeric($v)) {
					return htmlspecialchars($v);
				}

				$out = [];

				foreach ($config->options as $b => $name)
				{
					if ($v & (0x01 << $b))
						$out[] = $name;
				}

				return htmlspecialchars(implode(', ', $out));
			default:
				return nl2br(htmlspecialchars(rtrim((string) $v)));
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

		// Files are managed out of the form
		if ($config->type == 'file') {
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
			$options = [null => '— Non renseigné —'] + Utils::getCountryList();
			$params['default'] ??= Config::getInstance()->get('pays');
		}
		elseif ($type == 'date')
		{
			$params['pattern'] = '\d{4}-\d{2}-\d{2}';
		}

		$field = '';
		$value = $this->formField($params, false);
		$attributes = 'name="' . htmlspecialchars($params['name'], ENT_QUOTES, 'UTF-8') . '" ';
		$attributes .= 'id="f_' . htmlspecialchars($params['name'], ENT_QUOTES, 'UTF-8') . '" ';

		if ($params['name'] == 'numero') {
			$config->mandatory = true;
		}

		if ($params['name'] == 'numero' && $config->type == 'number' && !$value)
		{
			$value = DB::getInstance()->firstColumn('SELECT MAX(numero) + 1 FROM membres;');
		}

		if (!empty($params['disabled']))
		{
			$attributes .= 'disabled="disabled" ';
		}

		if (!empty($config->mandatory) && $type != 'checkbox' && $type != 'multiple')
		{
			$attributes .= 'required="required" ';
		}

		// Fix for autocomplete, lpignore is for Lastpass
		$attributes .= 'autocomplete="off" data-lpignore="true" ';

		if (!empty($params['user_mode']) && empty($config->editable))
		{
			$out = '<dt>' . htmlspecialchars($config->title, ENT_QUOTES, 'UTF-8') . '</dt>';
			$out .= '<dd>' . (trim((string) $value) === '' ? 'Non renseigné' : $this->displayChampMembre($value, $config)) . '</dd>';
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

				if ($value === $k || (empty($value) && !empty($params['default']) && $params['default'] === $value))
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
				$field .= sprintf('<input type="checkbox" name="%s[%d]" id="f_%1$s_%2$d" value="1" %s %s /> <label for="f_%1$s_%2$d">%s</label><br />',
					htmlspecialchars($params['name']), $k, ($value & $b) ? 'checked="checked"' : '', $attributes, htmlspecialchars($v));
			}
		}
		elseif ($type == 'textarea')
		{
			$field .= '<textarea ' . $attributes . 'cols="30" rows="5">' . htmlspecialchars((string) $value, ENT_QUOTES) . '</textarea>';
		}
		elseif ($type == 'date' || $type == 'datetime') {
			$field = self::formInput(['required' => $config->mandatory, 'name' => $params['name'], 'value' => $value, 'type' => $type, 'default' => $value]);
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
			elseif ($type == 'number') {
				$attributes .= 'step="any" ';
			}

			$field .= '<input type="' . $type . '" ' . $attributes . ' value="' . htmlspecialchars((string) $value, ENT_QUOTES) . '" />';
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

		$id_field = Config::getInstance()->get('champ_identifiant');

		if ($params['name'] == $id_field && empty($params['user_mode'])) {
			$out .= '<dd class="help"><small>(Sera utilisé comme identifiant de connexion si le membre a le droit de se connecter.)</small></dd>';
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
		if (isset($params['old'], $params['new'])) {
			$diff = \KD2\SimpleDiff::diff_to_array(false, $params['old'], $params['new'], $params['context'] ?? 3);
		}
		else {
			throw new \BadFunctionCallException('Paramètres old et new requis.');
		}

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

	protected function displayPermissions(array $params): string
	{
		$perms = $params['permissions'];

		$out = [];

		foreach (Category::PERMISSIONS as $name => $config) {
			$access = $perms->{'perm_' . $name};
			$label = $config['options'][$access];
			$out[$name] = sprintf('<b class="access_%s %s" title="%s">%s</b>', $access, $name, htmlspecialchars($label), $config['shape']);
		}

		return implode(' ', $out);
	}
}
