<?php

namespace Paheko\UserTemplate;

use Paheko\Config;
use Paheko\DB;
use Paheko\Entity;
use Paheko\Template;
use Paheko\Utils;
use Paheko\ValidationException;
use Paheko\Users\DynamicFields;
use Paheko\Users\Session;
use Paheko\Entities\Users\DynamicField;
use Paheko\Entities\Users\User;

use KD2\Form;

use const Paheko\{ADMIN_URL, CALC_CONVERT_COMMAND};

/**
 * Common functions used by Template (Smartyer) and UserTemplate
 */
class CommonFunctions
{
	const FUNCTIONS_LIST = [
		'input',
		'button',
		'link',
		'icon',
		'linkbutton',
		'linkmenu',
		'exportmenu',
		'delete_form',
		'edit_user_field',
		'user_field',
	];

	static public function input(array $params)
	{
		static $params_list = ['value', 'default', 'type', 'help', 'label', 'name', 'options', 'source', 'no_size_limit', 'copy', 'suffix'];

		// Extract params and keep attributes separated
		$attributes = array_diff_key($params, array_flip($params_list));
		$params = array_intersect_key($params, array_flip($params_list));
		extract($params, \EXTR_SKIP);

		if (!isset($name, $type)) {
			throw new \RuntimeException('Missing name or type');
		}

		$suffix = isset($suffix) ? ' ' . $suffix : null;

		if ($type == 'datetime') {
			$type = 'date';
			$tparams = func_get_arg(0);
			$tparams['type'] = 'time';
			$tparams['name'] = sprintf('%s_time', $name);
			unset($tparams['label']);
			$suffix = self::input($tparams);
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

		if ($type == 'date' || $type === 'time') {
			if ((is_string($current_value) && !preg_match('!^\d+:\d+$!', $current_value)) || is_int($current_value)) {
				try {
					$current_value = Entity::filterUserDateValue((string)$current_value);
				}
				catch (ValidationException $e) {
					$current_value = null;
				}
			}

			if (is_object($current_value) && $current_value instanceof \DateTimeInterface) {
				if ($type == 'date') {
					$current_value = $current_value->format('d/m/Y');
				}
				else {
					$current_value = $current_value->format('H:i');
				}
			}
		}
		elseif ($type == 'time' && is_object($current_value) && $current_value instanceof \DateTimeInterface) {
			$current_value = $current_value->format('H:i');
		}
		elseif ($type == 'password') {
			$current_value = null;
		}
		elseif ($type == 'time' && is_string($current_value)) {
			if ($v = \DateTime::createFromFormat('!Y-m-d H:i:s', $current_value)) {
				$current_value = $v->format('H:i');
			}
			elseif ($v = \DateTime::createFromFormat('!Y-m-d H:i', $current_value)) {
				$current_value = $v->format('H:i');
			}
		}

		$attributes['id'] = 'f_' . preg_replace('![^a-z0-9_-]!i', '', $name);
		$attributes['name'] = $name;

		if (!isset($attributes['autocomplete']) && ($type == 'money' || $type == 'password')) {
			$attributes['autocomplete'] = 'off';
		}

		if ($type == 'radio' || $type == 'checkbox' || $type == 'radio-btn') {
			$attributes['id'] .= '_' . (strlen($value) > 30 ? md5($value) : preg_replace('![^a-z0-9_-]!i', '', $value));

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
		elseif ($type == 'year') {
			$type = 'number';
			$attributes['size'] = 4;
			$attributes['maxlength'] = 4;
			$attributes['pattern'] = '\d';

		}
		elseif ($type == 'money') {
			$attributes['class'] = rtrim('money ' . ($attributes['class'] ?? ''));
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
			$v = sprintf('%s="%s"', $k, htmlspecialchars((string)$v));
		});

		$attributes_string = implode(' ', $attributes_string);

		if (isset($label)) {
			$label = htmlspecialchars((string)$label);
			$label = preg_replace_callback('!\[icon=([\w-]+)\]!', fn ($match) => self::icon(['shape' => $match[1]]), $label);
		}

		if ($type == 'radio-btn') {
			if (!empty($attributes['disabled'])) {
				$attributes['class'] = ($attributes['class'] ?? '') . ' disabled';
			}

			$radio = self::input(array_merge($params, ['type' => 'radio', 'label' => null, 'help' => null, 'disabled' => $attributes['disabled'] ?? null]));
			$out = sprintf('<dd class="radio-btn %s">%s
				<label for="%s"><div><h3>%s</h3>%s</div></label>
			</dd>', $attributes['class'] ?? '', $radio, $attributes['id'], $label, isset($params['help']) ? '<p class="help">' . nl2br(htmlspecialchars($params['help'])) . '</p>' : '');
			return $out;
		}
		if ($type == 'select') {
			$input = sprintf('<select %s>', $attributes_string);

			if (empty($attributes['required']) || isset($attributes['default_empty'])) {
				$input .= sprintf('<option value="">%s</option>', $attributes['default_empty'] ?? '');
			}

			if (!isset($options)) {
				throw new \RuntimeException('Missing "options" parameter');
			}

			foreach ($options as $_key => $_value) {
				$selected = null !== $current_value && ($current_value == $_key);
				$input .= sprintf('<option value="%s"%s>%s</option>', $_key, $selected ? ' selected="selected"' : '', htmlspecialchars((string)$_value));
			}

			$input .= '</select>';
		}
		elseif ($type == 'select_groups') {
			$input = sprintf('<select %s>', $attributes_string);

			if (empty($attributes['required'])) {
				$input .= '<option value=""></option>';
			}

			foreach ($options as $optgroup => $suboptions) {
				if (is_array($suboptions)) {
					$input .= sprintf('<optgroup label="%s">', htmlspecialchars((string)$optgroup));

					foreach ($suboptions as $_key => $_value) {
						$input .= sprintf('<option value="%s"%s>%s</option>', $_key, $current_value == $_key ? ' selected="selected"' : '', htmlspecialchars((string)$_value));
					}

					$input .= '</optgroup>';
				}
				else {
					$input .= sprintf('<option value="%s"%s>%s</option>', $optgroup, $current_value == $optgroup ? ' selected="selected"' : '', htmlspecialchars((string)$suboptions));
				}
			}

			$input .= '</select>';
		}
		elseif ($type == 'textarea') {
			$input = sprintf('<textarea %s>%s</textarea>', $attributes_string, htmlspecialchars((string)$current_value));
		}
		elseif ($type == 'list') {
			$multiple = !empty($attributes['multiple']);
			$can_delete = $multiple || !empty($attributes['can_delete']);
			$values = '';
			$delete_btn = self::button(['shape' => 'delete']);

			if (null !== $current_value && (is_array($current_value) || is_object($current_value))) {
				foreach ($current_value as $v => $l) {
					if (empty($l) || trim($l) === '') {
						continue;
					}

					$values .= sprintf('<span class="label"><input type="hidden" name="%s[%s]" value="%s" /> %3$s %s</span>', htmlspecialchars((string)$name), htmlspecialchars((string)$v), htmlspecialchars((string)$l), $can_delete ? $delete_btn : '');
				}
			}

			$button = self::button([
				'shape' => $multiple ? 'plus' : 'menu',
				'label' => $multiple ? 'Ajouter' : 'Sélectionner',
				'required' => $attributes['required'] ?? null,
				'value' => Utils::getLocalURL($attributes['target']),
				'data-multiple' => $multiple ? '1' : '0',
				'data-can-delete' => (int) $can_delete,
				'data-name' => $name,
			]);

			$input = sprintf('<span id="%s_container" class="input-list">%s%s</span>', htmlspecialchars($attributes['id']), $button, $values);
		}
		elseif ($type == 'money') {
			if (null !== $current_value && !$current_value_from_user) {
				$current_value = Utils::money_format($current_value, ',', '');
			}

			if ((string) $current_value === '0') {
				$current_value = '';
			}

			$currency = Config::getInstance()->currency;
			$input = sprintf('<nobr><input type="text" pattern="-?[0-9]+([.,][0-9]{1,2})?" inputmode="decimal" size="8" %s value="%s" /><b>%s</b></nobr>', $attributes_string, htmlspecialchars((string) $current_value), $currency);
		}
		else {
			$value = isset($attributes['value']) ? '' : sprintf(' value="%s"', htmlspecialchars((string)$current_value));
			$input = sprintf('<input type="%s" %s %s />', $type, $attributes_string, $value);
		}

		if ($type == 'file') {
			$input .= sprintf('<input type="hidden" name="MAX_FILE_SIZE" value="%d" id="f_maxsize" />', Utils::return_bytes(Utils::getMaxUploadSize()));
		}
		elseif (!empty($copy)) {
			$input .= sprintf('<input type="button" onclick="var a = $(\'#f_%s\'); a.focus(); a.select(); document.execCommand(\'copy\'); this.value = \'Copié !\'; this.focus(); return false;" onblur="this.value = \'Copier\';" value="Copier" title="Copier dans le presse-papier" />', $params['name']);
		}

		$input .= $suffix;

		// No label? then we only want the input without the widget
		if (empty($label)) {
			if (!array_key_exists('label', $params) && ($type == 'radio' || $type == 'checkbox')) {
				$input .= sprintf('<label for="%s"></label>', $attributes['id']);
			}

			return $input;
		}

		$label = sprintf('<label for="%s">%s</label>', $attributes['id'], $label);

		if ($type == 'radio' || $type == 'checkbox') {
			$out = sprintf('<dd>%s %s', $input, $label);

			if (isset($help)) {
				$out .= sprintf(' <em class="help">(%s)</em>', htmlspecialchars($help));
			}

			$out .= '</dd>';
		}
		else {
			$out = sprintf('<dt>%s%s</dt><dd>%s</dd>', $label, $required_label, $input);

			if ($type == 'file' && empty($params['no_size_limit'])) {
				$out .= sprintf('<dd class="help"><small>Taille maximale : %s</small></dd>', Utils::format_bytes(Utils::getMaxUploadSize()));
			}

			if (isset($help)) {
				$out .= sprintf('<dd class="help">%s</dd>', nl2br(htmlspecialchars($help)));
			}
		}

		return $out;
	}

	static public function icon(array $params): string
	{
		if (isset($params['shape']) && isset($params['html']) && $params['html'] == false) {
			return Utils::iconUnicode($params['shape']);
		}

		if (!isset($params['shape']) && !isset($params['url'])) {
			throw new \RuntimeException('Missing parameter: shape or url');
		}

		$html = '';

		if (isset($params['url'])) {
			$html = self::getIconHTML(['icon' => $params['url']]);
			unset($params['url']);
		}

		$html .= htmlspecialchars($params['label'] ?? '');
		unset($params['label']);

		self::setIconAttribute($params);

		$attributes = array_diff_key($params, ['shape']);
		$attributes = array_map(fn($v, $k) => sprintf('%s="%s"', $k, htmlspecialchars($v)),
			$attributes, array_keys($attributes));

		$attributes = implode(' ', $attributes);

		return sprintf('<span %s>%s</span>', $attributes, $html);
	}

	static public function link(array $params): string
	{
		$href = $params['href'];
		$label = $params['label'];
		$prefix = $params['prefix'] ?? '';

		// href can be prefixed with '!' to make the URL relative to ADMIN_URL
		if (substr($href, 0, 1) == '!') {
			$href = ADMIN_URL . substr($params['href'], 1);
		}

		// propagate _dialog param if we are in an iframe
		if (isset($_GET['_dialog']) && !isset($params['target'])) {
			$href .= (strpos($href, '?') === false ? '?' : '&') . '_dialog';
		}

		if (!isset($params['class'])) {
			$params['class'] = '';
		}

		unset($params['href'], $params['label'], $params['prefix']);

		array_walk($params, function (&$v, $k) {
			$v = sprintf('%s="%s"', $k, htmlspecialchars((string)$v));
		});

		$params = implode(' ', $params);

		$label = $label ? sprintf('<span>%s</span>', htmlspecialchars($label)) : '';

		return sprintf('<a href="%s" %s>%s%s</a>', htmlspecialchars($href), $params, $prefix, $label);
	}

	static public function button(array $params): string
	{
		$label = isset($params['label']) ? htmlspecialchars((string)$params['label']) : '';
		unset($params['label']);

		self::setIconAttribute($params);

		if (!isset($params['type'])) {
			$params['type'] = 'button';
		}

		if (!isset($params['class'])) {
			$params['class'] = '';
		}

		if (isset($params['name']) && !isset($params['value'])) {
			$params['value'] = 1;
		}

		$prefix = '';
		$suffix = '';

		if (isset($params['icon'])) {
			$prefix = self::getIconHTML($params);
			unset($params['icon'], $params['icon_html']);
		}

		if (isset($params['csrf_key'])) {
			$suffix .= Form::tokenHTML($params['csrf_key']);
			unset($params['csrf_key']);
		}

		$params['class'] .= ' icn-btn';

		// Remove NULL params
		$params = array_filter($params);

		array_walk($params, function (&$v, $k) {
			$v = sprintf('%s="%s"', $k, htmlspecialchars((string)$v));
		});

		$params = implode(' ', $params);

		return sprintf('<button %s>%s%s</button>%s', $params, $prefix, $label, $suffix);
	}

	static public function linkbutton(array $params): string
	{
		self::setIconAttribute($params);

		if (isset($params['icon']) || isset($params['icon_html'])) {
			$params['prefix'] = self::getIconHTML($params);
			unset($params['icon'], $params['icon_html']);
		}

		if (!isset($params['class'])) {
			$params['class'] = '';
		}

		$params['class'] .= ' icn-btn';

		return self::link($params);
	}

	static protected function getIconHTML(array $params): string
	{
		if (isset($params['icon_html'])) {
			return '<i class="icon">' . $params['icon_html'] . '</i>';
		}

		return sprintf('<svg class="icon"><use xlink:href="%s#img" href="%1$s#img"></use></svg> ',
			htmlspecialchars(Utils::getLocalURL($params['icon']))
		);
	}

	static protected function setIconAttribute(array &$params): void
	{
		if (isset($params['shape'])) {
			$params['data-icon'] = Utils::iconUnicode($params['shape']);
		}

		unset($params['shape']);
	}

	static public function exportmenu(array $params): string
	{
		$url = $params['href'] ?? Utils::getSelfURI();
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

		if (!empty($params['form'])) {
			$name = $params['name'] ?? 'export';
			$out = self::button(['value' => 'csv', 'shape' => 'export', 'label' => 'Export CSV', 'name' => $name, 'type' => 'submit']);
			$out .= self::button(['value' => 'ods', 'shape' => 'export', 'label' => 'Export LibreOffice', 'name' => $name, 'type' => 'submit']);

			if ($xlsx) {
				$out .= self::button(['value' => 'xlsx', 'shape' => 'export', 'label' => 'Export Excel', 'name' => $name, 'type' => 'submit']);
			}
		}
		else {
			$out  = self::linkButton(['href' => $url . 'csv', 'label' => 'Export CSV', 'shape' => 'export']);
			$out .= ' ' . self::linkButton(['href' => $url . 'ods', 'label' => 'Export LibreOffice', 'shape' => 'export']);

			if ($xlsx !== false) {
				$out .= ' ' . self::linkButton(['href' => $url . 'xlsx', 'label' => 'Export Excel', 'shape' => 'export']);
			}
		}

		$params = array_merge($params, ['shape' => 'export', 'label' => $params['label'] ?? 'Export…']);
		return self::linkmenu($params, $out);
	}

	static public function linkmenu(array $params, ?string $content): string
	{
		if (null === $content) {
			return '';
		}

		if (!empty($params['right'])) {
			$params['class'] = 'menu-btn-right';
		}

		$out = sprintf('
			<span class="menu-btn %s">
				<b data-icon="%s" class="btn" ondblclick="this.parentNode.querySelector(\'a, button\').click();" onclick="this.parentNode.classList.toggle(\'active\');">%s</b>
				<span><span>',
			htmlspecialchars($params['class'] ?? ''),
			Utils::iconUnicode($params['shape']),
			htmlspecialchars($params['label'])
		);

		$out .= $content . '</span></span>
			</span>';

		return $out;
	}

	static public function delete_form(array $params): string
	{
		if (!isset($params['legend'], $params['warning'], $params['csrf_key'])) {
			throw new \InvalidArgumentException('Missing parameter: legend, warning and csrf_key are required');
		}

		$tpl = Template::getInstance();
		$tpl->assign($params);
		return $tpl->fetch('common/delete_form.tpl');
	}

	static public function edit_user_field(array $params): string
	{
		if (isset($params['field'])) {
			$field = $params['field'];
		}
		else {
			$name = $params['name'] ?? $params['key'] ?? null;

			if (null === $name) {
				throw new \RuntimeException('Missing "name" parameter');
			}

			$field = DynamicFields::get($name);
		}

		if (!($field instanceof DynamicField)) {
			throw new \LogicException('This field does not exist.');
		}

		$context = $params['context'] ?? 'module';

		if (!in_array($context, ['user_edit', 'admin_new', 'admin_edit', 'module'])) {
			throw new \InvalidArgumentException('Invalid "context" parameter value: ' . $context);
		}

		$source = $params['user'] ?? $params['source'] ?? null;

		$name = $field->name;
		$type = $field->type;

		// The password must be changed using a specific field
		if ($field->system & $field::PASSWORD) {
			return '';
		}
		// Files are managed out of the form
		elseif ($type == 'file') {
			return '';
		}
		// VIRTUAL columns cannot be edited
		elseif ($type == 'virtual') {
			return '';
		}
		elseif ($context === 'user_edit' && $field->user_access_level === Session::ACCESS_NONE) {
			return '';
		}
		elseif ($context === 'user_edit' && $field->user_access_level === Session::ACCESS_READ) {
			$v = self::user_field(['name' => $name, 'value' => $params['user']->$name]);
			return sprintf('<dt>%s</dt><dd>%s</dd>', $field->label, $v ?: '<em>Non renseigné</em>');
		}

		$params = [
			'type'     => $type,
			'name'     => $name,
			'label'    => $field->label,
			'required' => $field->required,
			'source'   => $source,
			'disabled' => !empty($disabled),
			'required' => $field->required && $type != 'checkbox',
			'help'     => $field->help,
			// Fix for autocomplete, lpignore is for Lastpass
			'autocomplete' => 'off',
			'data-lpignore' => 'true',
		];

		// Multiple choice checkboxes is a specific thingy
		if ($type == 'multiple') {
			$options = $field->options;

			if (isset($_POST[$name]) && is_array($_POST[$name])) {
				$value = 0;

				foreach ($_POST[$name] as $k => $v) {
					if (array_key_exists($k, $options) && !empty($v)) {
						$value |= 0x01 << $k;
					}
				}
			}
			else {
				$value = $params['source']->$name ?? null;
			}

			// Forcer la valeur à être un entier (depuis PHP 7.1)
			$value = (int)$value;

			if ($field->required) {
				$required_label =  ' <b title="Champ obligatoire">(obligatoire)</b>';
			}
			else {
				$required_label =  ' <i>(facultatif)</i>';
			}

			$out  = sprintf('<dt><label for="f_%s_0">%s</label>%s<input type="hidden" name="%s_present" value="1" /></dt>', $name, htmlspecialchars($field->label), $required_label, $name);

			if ($field->help ?? null) {
				$out .= sprintf('<dd class="help">%s</dd>', htmlspecialchars($field->help));
			}

			foreach ($options as $k => $v)
			{
				$b = 0x01 << (int)$k;

				$p = [
					'type'    => 'checkbox',
					'label'   => $v,
					'value'   => 1,
					'default' => ($value & $b) ? 1 : 0,
					'name'    => sprintf('%s[%d]', $name, $k),
				];

				$out .= CommonFunctions::input($p);
			}

			return $out;
		}
		elseif ($type == 'select') {
			$params['options'] = array_combine($field->options, $field->options);
		}
		elseif ($type == 'country') {
			$params['type'] = 'select';
			$params['options'] = Utils::getCountryList();
			$params['default'] = Config::getInstance()->get('country');
		}
		elseif ($type == 'checkbox') {
			$params['value'] = 1;
			$params['label'] = 'Oui';

			if ($field->required) {
				$required_label =  ' <b title="Champ obligatoire">(obligatoire)</b>';
			}
			else {
				$required_label =  ' <i>(facultatif)</i>';
			}

			return sprintf('<dt><label for="f_%s_1">%s %s</label><input type="hidden" name="%1$s_present" value="1" /></dt>%s', $field->name, htmlspecialchars($field->label), $required_label, CommonFunctions::input($params));
		}
		elseif ($field->system & $field::NUMBER && $context === 'admin_new') {
			$params['default'] = DB::getInstance()->firstColumn(sprintf('SELECT MAX(%s) + 1 FROM %s;', $name, User::TABLE));
			$params['required'] = false;
		}
		elseif ($type === 'number') {
			$params['step'] = '1';
			$params['pattern'] = '\\d+';
		}
		elseif ($type === 'decimal') {
			$params['type'] = 'number';
			$params['step'] = 'any';
		}
		elseif ($type === 'datalist') {
			$options = '';

			foreach ($field->options as $value) {
				$options .= sprintf('<option>%s</option>', htmlspecialchars($value));
			}

			$params['type'] = 'text';
			$params['list'] = 'list-' . $params['name'];
			$params['suffix'] = sprintf('<datalist id="%s">%s</datalist>', $params['list'], $options);
		}

		if ($field->default_value === 'NOW()') {
			$params['default'] = new \DateTime;
		}
		elseif (!empty($field->default_value)) {
			$params['default'] = $field->default_value;
		}

		$out = CommonFunctions::input($params);

		if (($context === 'admin_new' || $context === 'admin_edit') && $field->system & $field::LOGIN) {
			$out .= '<dd class="help"><small>(Sera utilisé comme identifiant de connexion si le membre a le droit de se connecter.)</small></dd>';
		}

		if ($context === 'admin_new' && $field->system & $field::NUMBER) {
			$out .= '<dd class="help"><small>Doit être unique, laisser vide pour que le numéro soit attribué automatiquement.</small></dd>';
		}
		elseif ($context === 'admin_edit' && $field->system & $field::NUMBER) {
			$out .= '<dd class="help"><small>Doit être unique pour chaque membre.</small></dd>';
		}

		return $out;
	}

	static public function user_field(array $params): string
	{
		if (isset($params['field'])) {
			$field = $params['field'];
		}
		else {
			$name = $params['name'] ?? $params['key'] ?? null;

			if (null === $name) {
				throw new \RuntimeException('Missing "name" parameter');
			}

			$field = DynamicFields::get($name);
		}

		if ($field && !($field instanceof DynamicField)) {
			throw new \LogicException('This field does not exist.');
		}

		$v = $params['value'] ?? null;

		$out = '';

		if (!$field) {
			$out = htmlspecialchars((string)$v);
		}
		elseif ($field->type == 'checkbox') {
			$out = $v ? 'Oui' : 'Non';
		}
		elseif (null === $v) {
			return '';
		}
		elseif ($field->type == 'file') {
			if (!$v) {
				return '';
			}

			$files = explode(';', $v);
			$count = 0;
			$label = '';

			foreach ($files as $path) {
				if (!preg_match('!\.(?:png|jpe?g|gif|webp)$!i', $path)) {
					$count++;
					continue;
				}
				elseif ($label !== '') {
					$count++;
					continue;
				}

				$url = BASE_URL . $path . '?150px';
				$label .= sprintf(
					'<img src="%s" alt="%s" />',
					htmlspecialchars($url),
					htmlspecialchars($field->label)
				);
			}

			if ($count) {
				$label .= ($count != count($files) ? '+' : '')
					. ($count == 1 ? '1 fichier' : $count . ' fichiers');
			}

			if ($label !== '') {
				if (isset($params['files_href'])) {
					$label = sprintf('<a href="%s">%s</a>', Utils::getLocalURL($params['files_href']), $label);
				}

				$out = '<div class="files-list"><figure>' . $label . '</label></div>';
			}
		}
		elseif ($field->type === 'password') {
			$out = '*****';
		}
		elseif ($field->type === 'email' && empty($params['link_name_id'])) {
			$out = '<a href="mailto:' . rawurlencode($v) . '">' . htmlspecialchars($v) . '</a>';
		}
		elseif ($field->type === 'tel' && empty($params['link_name_id'])) {
			$out = '<a href="tel:' . rawurlencode($v) . '">' . htmlspecialchars(CommonModifiers::format_phone_number($v)) . '</a>';
		}
		elseif ($field->type === 'url' && empty($params['link_name_id'])) {
			$out ='<a href="' . htmlspecialchars($v) . '" target="_blank">' . htmlspecialchars($v) . '</a>';
		}
		elseif ($field->type === 'number' || $field->type === 'decimal') {
			$out = str_replace('.', ',', htmlspecialchars($v));
		}
		else {
			$v = $field->getStringValue($v);
			$out = nl2br(htmlspecialchars((string) $v));
		}

		if (!empty($params['link_name_id']) && ($name === 'identity' || ($field && $field->isName() && substr($out, 0, 2) !== '<a'))) {
			$out = sprintf('<a href="%s">%s</a>', Utils::getLocalURL('!users/details.php?id=' . (int)$params['link_name_id']), $out);
		}

		return $out;
	}
}
