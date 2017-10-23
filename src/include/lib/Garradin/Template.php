<?php

namespace Garradin;

use KD2\Form;

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
		if (!file_exists(CACHE_ROOT . '/compiled'))
		{
			mkdir(CACHE_ROOT . '/compiled');
		}

		self::setCompileDir(CACHE_ROOT . '/compiled');
		self::setTemplateDir(ROOT . '/templates');

		parent::__construct();

		$this->assign('www_url', WWW_URL);
		$this->assign('self_url', Utils::getSelfUrl());
		$this->assign('self_url_no_qs', Utils::getSelfUrl(false));

		$this->assign('is_logged', false);

		$this->register_compile_function('continue', function ($pos, $block, $name, $raw_args) {
			if ($block == 'continue')
			{
				return 'continue;';
			}
		});

		$this->register_function('form_errors', [$this, 'formErrors']);
		$this->register_function('show_error', [$this, 'showError']);
		$this->register_function('form_field', [$this, 'formField']);
		$this->register_function('select_compte', [$this, 'formSelectCompte']);
		$this->register_function('html_champ_membre', [$this, 'formChampMembre']);

		$this->register_function('custom_colors', [$this, 'customColors']);
		$this->register_function('plugin_url', ['Garradin\Utils', 'plugin_url']);
		$this->register_function('diff', [$this, 'diff']);
		$this->register_function('pagination', [$this, 'pagination']);
		$this->register_function('format_droits', [$this, 'formatDroits']);

		$this->register_function('csrf_field', function ($params) {
			return Form::tokenHTML($params['key']);
		});

		$this->register_modifier('strlen', 'strlen');
		$this->register_modifier('get_country_name', ['Garradin\Utils', 'getCountryName']);
		$this->register_modifier('format_sqlite_date_to_french', ['Garradin\Utils', 'sqliteDateToFrench']);
		$this->register_modifier('format_bytes', ['Garradin\Utils', 'format_bytes']);
		$this->register_modifier('format_tel', [$this, 'formatPhoneNumber']);
		$this->register_modifier('abs', 'abs');
		$this->register_modifier('display_champ_membre', [$this, 'displayChampMembre']);
		
		$this->register_modifier('get_nom_compte', function ($compte) {
			if (is_null($compte))
			{
				return '';
			}

			if (!isset($this->liste_comptes))
			{
				$this->liste_comptes = (new Compta\Comptes)->getListAll();
			}

			return $this->liste_comptes[$compte];
		});

		$this->register_modifier('strftime_fr', function ($ts, $format) {
			return Utils::strftime_fr($format, $ts);
		});

		$this->register_modifier('date_fr', function ($ts, $format = 'd/m/Y H:i:s') {
			return Utils::date_fr($format, $ts);
		});

		$this->register_modifier('escape_money', function ($number) {
			return number_format((float)$number, 2, ',', ' ');
		});

		$this->register_modifier('html_money', function ($number) {
			return '<b class="money">' . number_format((float)$number, 2, ',', '&nbsp;') . '</b>';
		});

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

	protected function formErrors($params)
	{
		$form = $this->getTemplateVars('form');

		if (!$form->hasErrors())
		{
			return '';
		}

		return '<div class="error"><ul><li>' . implode('</li><li>', $form->getErrorMessages(!empty($params['membre']) ? true : false)) . '</li></ul></div>';
	}

	protected function showError($params)
	{
		if (!$params['if'])
		{
			return '';
		}

		return '<p class="error">' . $this->escape($params['message']) . '</p>';
	}

	protected function formField(array $params)
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

		return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
	}

	protected function formatPhoneNumber($n)
	{
		$n = preg_replace('![^\d\+]!', '', $n);

		if (substr($n, 0, 1) == '+')
		{
			$n = preg_replace('!^\+(?:1|2[07]|2\d{2}|3[0-469]|3\d{2}|4[013-9]|'
				. '4\d{2}|5[1-8]|5\d{2}|6[0-6]|6\d{2}|7\d|8[1-469]|8\d{2}|'
				. '9[0-58]|9\d{2})!', '\\0 ', $n);
		}
		elseif (preg_match('/^\d{10}$/', $n))
		{
			$n = preg_replace('!(\d{2})!', '\\1 ', $n);
		}

		return $n;
	}

	protected function customColors()
	{
		$config = Config::getInstance();

		if (!$config->get('couleur1') || !$config->get('couleur2') || !$config->get('image_fond'))
		{
			return '';
		}

		$couleur1 = implode(', ', sscanf($config->get('couleur1'), '#%02x%02x%02x'));
		$couleur2 = implode(', ', sscanf($config->get('couleur2'), '#%02x%02x%02x'));

		$f = new Fichiers($config->get('image_fond'));
		$image_fond = $f->getURL();

		$out = '
		<style type="text/css">
		:root {
			--gMainColor: %s;
			--gSecondColor: %s;
		}
		.header .menu, body {
			background-image: url("%s");
		}
		</style>';

		return sprintf($out, $couleur1, $couleur2, $image_fond);
	}

	protected function displayChampMembre($v, $config)
	{
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
			case 'multiple':
				$out = [];

				foreach ($config->options as $b => $name)
				{
					if ($v & (0x01 << $b))
						$out[] = $name;
				}

				return implode(', ', $out);
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
		$value = $this->formField($params);
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
			$field .= '<textarea ' . $attributes . 'cols="30" rows="5">' . $value . '</textarea>';
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

			$field .= '<input type="' . $type . '" ' . $attributes . ' value="' . $value . '" />';
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

	protected function formSelectCompte($params)
	{
		$name = $params['name'];
		$comptes = $params['comptes'];
		$data = isset($params['data']) ? (array) $params['data'] : null;
		$selected = isset($data[$params['name']]) ? $data[$params['name']] : f($name);

		$out = '<select name="'.$name.'" id="f_'.$name.'" class="large">';

		foreach ($comptes as $compte)
		{
			// Ne pas montrer les comptes désactivés
			if (!empty($compte->desactive))
				continue;

			if (!isset($compte->id[1]) && empty($params['create']))
			{
				$out.= '<optgroup label="'.htmlspecialchars($compte->libelle, ENT_QUOTES, 'UTF-8', false).'" class="niveau_1"></optgroup>';
			}
			elseif (!isset($compte->id[2]) && empty($params['create']))
			{
				if ($compte->id > 10)
					$out.= '</optgroup>';

				$out.= '<optgroup label="'.htmlspecialchars($compte->id . ' - ' . $compte->libelle, ENT_QUOTES, 'UTF-8', false).'" class="niveau_2">';
			}
			else
			{
				$out .= '<option value="'.htmlspecialchars($compte->id, ENT_QUOTES, 'UTF-8', false).'" class="niveau_'.strlen($compte->id).'"';

				if ($selected == $compte->id)
				{
					$out .= ' selected="selected"';
				}

				$out .= '>' . htmlspecialchars($compte->id . ' - ' . $compte->libelle, ENT_QUOTES, 'UTF-8', false);
				$out .= '</option>';
			}
		}

		$out .= '</optgroup>';
		$out .= '</select>';

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
