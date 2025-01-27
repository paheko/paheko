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
use Paheko\Users\Users;
use Paheko\Entities\Users\DynamicField;
use Paheko\Entities\Users\User;
use Paheko\Files\Conversion;
use Paheko\Files\Files;

use KD2\Form;

use const Paheko\{ADMIN_URL, BASE_URL, LOCAL_ADDRESSES_ROOT};

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
		'tag',
		'dropdown',
	];

	static public function input(array $params)
	{
		static $params_list = ['value', 'default', 'type', 'help', 'label', 'name', 'options', 'source', 'no_size_limit', 'copy', 'suffix', 'prefix_title', 'prefix_help', 'prefix_required', 'datalist'];

		// Extract params and keep attributes separated
		$attributes = array_diff_key($params, array_flip($params_list));
		$params = array_intersect_key($params, array_flip($params_list));
		extract($params, \EXTR_SKIP);

		if (!isset($name, $type)) {
			throw new \RuntimeException('Missing name or type');
		}

		$suffix = isset($suffix) ? ' ' . $suffix : null;

		if ($type === 'datetime') {
			$type = 'date';
			$tparams = func_get_arg(0);
			$tparams['type'] = 'time';
			$tparams['name'] = sprintf('%s_time', $name);
			unset($tparams['label']);
			$suffix = self::input($tparams);
		}

		if ($type == 'file' && isset($attributes['accept']) && $attributes['accept'] == 'csv') {
			$attributes['accept'] = '.csv,text/csv,application/csv,.CSV';
			$help = ($help ?? '') . PHP_EOL . 'Format accepté : CSV';

			if (Conversion::canConvertToCSV()) {
				$help .= ', LibreOffice Calc (ODS), ou Excel (XLSX)';
				$attributes['accept'] .= ',.ods,.ODS,application/vnd.oasis.opendocument.spreadsheet'
					. ',.xls,.XLS,application/vnd.ms-excel'
					. ',.xlsx,.XLSX,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
			}
		}

		$current_value = null;
		$current_value_from_user = false;
		$source_name = ($type === 'time') ? str_replace('_time', '', $name) : $name;

		if (isset($_POST[$name])) {
			$current_value = $_POST[$name];
			$current_value_from_user = true;
		}
		elseif (isset($source) && is_object($source) && isset($source->$source_name) && !is_null($source->$source_name)) {
			$current_value = $source->$source_name;
		}
		elseif (isset($source) && is_array($source) && isset($source[$source_name])) {
			$current_value = $source[$source_name];
		}
		elseif (isset($default) && ($type != 'checkbox' || empty($_POST))) {
			$current_value = $default;
		}

		if ($type === 'date' || $type === 'time') {
			if ((is_string($current_value) && !preg_match('!^\d+:\d+$!', $current_value)) || is_int($current_value)) {
				try {
					$current_value = Entity::filterUserDateValue((string)$current_value);
				}
				catch (ValidationException $e) {
					$current_value = null;
				}
			}

			if (is_object($current_value) && $current_value instanceof \DateTimeInterface) {
				if ($type === 'date') {
					$current_value = $current_value->format('d/m/Y');
				}
				else {
					$current_value = $current_value->format('H:i');
				}
			}
		}
		elseif ($type == 'password') {
			$current_value = null;
		}

		$attributes['id'] = 'f_' . preg_replace('![^a-z0-9_-]!i', '', $name);
		$attributes['name'] = $name;

		if (!isset($attributes['autocomplete']) && ($type == 'money' || $type == 'password')) {
			$attributes['autocomplete'] = 'off';
		}

		if ($type == 'radio' || $type == 'checkbox' || $type == 'radio-btn') {
			if (!isset($value)) {
				throw new \InvalidArgumentException('radio/checkbox has no "value" parameter');
			}

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
		elseif ($type == 'month') {
			$attributes['size'] = 7;
			$attributes['maxlength'] = 7;
			$attributes['pattern'] = '\d{4}-\d{2}';
			$attributes['placeholder'] = 'AAAA-MM';
		}
		elseif ($type == 'weight') {
			$type = 'number';
			$attributes['placeholder'] = '1,312';
			$attributes['size'] = 10;
			$attributes['step'] = '0.001';
			$suffix = ' kg';

			if (null !== $current_value && !$current_value_from_user) {
				$current_value = str_replace(',', '.', Utils::format_weight($current_value));
			}
		}
		elseif ($type == 'money') {
			$attributes['class'] = rtrim('money ' . ($attributes['class'] ?? ''));
		}

		if (!empty($params['datalist'])) {
			$list = '';
			$list_attributes = '';

			if (is_array($params['datalist'])) {
				foreach ($params['datalist'] as $value) {
					$list .= sprintf('<option>%s</option>', htmlspecialchars($value));
				}
			}
			elseif ($params['datalist'] === 'address' && LOCAL_ADDRESSES_ROOT && file_exists(LOCAL_ADDRESSES_ROOT . '/fr.sqlite')) {
				$list_attributes = ' data-autocomplete="address"';
			}
			elseif ($params['datalist'] !== 'address') {
				$list_attributes = sprintf(' data-autocomplete="%s"', $params['datalist']);
			}

			$attributes['list'] = 'list-' . $attributes['id'];
			$suffix = sprintf('<datalist id="%s"%s>%s</datalist>', $attributes['list'], $list_attributes, $list);
		}

		// Create attributes string
		if (!empty($attributes['required'])) {
			$attributes['required'] = 'required';
		}
		else {
			unset($attributes['required']);
		}

		if (!empty($attributes['autofocus'])) {
			$attributes['autofocus'] = 'autofocus';
		}
		else {
			unset($attributes['autofocus']);
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

		if (!empty($attributes['required']) || !empty($params['prefix_required'])) {
			$required_label =  ' <b title="Champ obligatoire">(obligatoire)</b>';
		}
		elseif ($type !== 'password') {
			$required_label =  ' <i>(facultatif)</i>';
		}
		else {
			$required_label = '';
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

		if ($type === 'country') {
			$type = 'select';
			$options = Utils::getCountryList();

			// In case $current_value holds the country name instead of the country code
			if ($current_value
				&& ($code = array_search($current_value, $options, true))) {
				$current_value = $code;
			}
		}

		$label ??= null;
		$options ??= null;

		if ($type === 'radio-btn') {
			if (!empty($attributes['disabled'])) {
				$attributes['class'] = ($attributes['class'] ?? '') . ' disabled';
			}

			$radio = self::input(array_merge($params, ['type' => 'radio', 'label' => null, 'help' => null, 'disabled' => $attributes['disabled'] ?? null]));

			$input = sprintf('<dd class="radio-btn %s">%s
				<label for="%s"><div><h3>%s</h3>%s</div></label>
			</dd>',
				$attributes['class'] ?? '',
				$radio,
				$attributes['id'],
				$label,
				isset($params['help']) ? '<p class="help">' . nl2br(htmlspecialchars($params['help'])) . '</p>' : ''
			);

			unset($help, $label);
		}
		elseif ($type === 'select') {
			$input = sprintf('<select %s>', $attributes_string);

			if (empty($attributes['required']) || isset($attributes['default_empty'])) {
				$input .= sprintf('<option value="">%s</option>', $attributes['default_empty'] ?? '');
			}

			if (!isset($options)) {
				throw new \RuntimeException('Missing "options" parameter');
			}

			foreach ($options as $_key => $_value) {
				$selected = null !== $current_value && ($current_value == $_key);
				$input .= sprintf('<option value="%s"%s>%s</option>', htmlspecialchars($_key), $selected ? ' selected="selected"' : '', htmlspecialchars((string)$_value));
			}

			$input .= '</select>';
		}
		elseif ($type === 'select_groups') {
			$input = sprintf('<select %s>', $attributes_string);

			if (empty($attributes['required']) || isset($attributes['default_empty'])) {
				$input .= sprintf('<option value="">%s</option>', $attributes['default_empty'] ?? '');
			}

			foreach ($options as $optgroup => $suboptions) {
				// Accept [['label' => 'optgroup label', 'options' => ['key1' => 'option 1']]]
				if (isset($suboptions['options'])) {
					$input .= sprintf('<optgroup label="%s">', htmlspecialchars((string)$suboptions['label']));

					foreach ($suboptions['options'] as $_key => $_value) {
						$input .= sprintf('<option value="%s"%s>%s</option>', $_key, $current_value == $_key ? ' selected="selected"' : '', htmlspecialchars((string)$_value));
					}

					$input .= '</optgroup>';
				}
				// Accept ['optgroup label' => ['key1' => 'option 1']]
				elseif (is_array($suboptions)) {
					$input .= sprintf('<optgroup label="%s">', htmlspecialchars((string)$optgroup));

					foreach ($suboptions as $_key => $_value) {
						$input .= sprintf('<option value="%s"%s>%s</option>', $_key, $current_value == $_key ? ' selected="selected"' : '', htmlspecialchars((string)$_value));
					}

					$input .= '</optgroup>';
				}
				// Accept ['key1' => 'option 1']
				else {
					$input .= sprintf('<option value="%s"%s>%s</option>', $optgroup, $current_value == $optgroup ? ' selected="selected"' : '', htmlspecialchars((string)$suboptions));
				}
			}

			$input .= '</select>';
		}
		elseif ($type === 'textarea') {
			$input = sprintf('<textarea %s>%s</textarea>', $attributes_string, htmlspecialchars((string)$current_value));
		}
		elseif ($type === 'list') {
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
				'shape'           => $multiple ? 'plus' : 'menu',
				'label'           => $multiple ? 'Ajouter' : 'Sélectionner',
				'required'        => $attributes['required'] ?? null,
				'value'           => Utils::getLocalURL($attributes['target']),
				'data-caption'    => $params['label'] ?? '',
				'data-multiple'   => $multiple ? '1' : '0',
				'data-can-delete' => (int) $can_delete,
				'data-name'       => $name,
				'data-max'        => $attributes['max'] ?? 0,
			]);

			$input = sprintf('<span id="%s_container" class="input-list">%s%s</span>', htmlspecialchars($attributes['id']), $button, $values);
		}
		elseif ($type === 'money') {
			if (null !== $current_value && !$current_value_from_user) {
				$current_value = Utils::money_format($current_value, ',', '');
			}

			if ((string) $current_value === '0') {
				$current_value = '';
			}

			$currency = Config::getInstance()->currency;
			$input = sprintf('<nobr><input type="text" pattern="\s*-?[0-9 ]+([.,][0-9]{1,2})?\s*" inputmode="decimal" size="8" %s value="%s" /><b>%s</b></nobr>', $attributes_string, htmlspecialchars((string) $current_value), $currency);
		}
		else {
			$value = isset($attributes['value']) ? '' : sprintf(' value="%s"', htmlspecialchars((string)$current_value));
			$input = sprintf('<input type="%s" %s %s />', $type, $attributes_string, $value);
		}

		if ($type === 'file') {
			$input .= sprintf('<input type="hidden" name="MAX_FILE_SIZE" value="%d" id="f_maxsize" />', Utils::return_bytes(Utils::getMaxUploadSize()));
		}
		elseif ($type === 'checkbox') {
			$input = sprintf('<input type="hidden" name="%s" value="1" />', preg_replace('/(?=\[|$)/', '_present', $name, 1)) . $input;
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

		$out = '';

		if (!empty($params['prefix_title'])) {
			$out .= sprintf('<dt><label for="%s">%s</label>%s</dt>',
				$attributes['id'],
				htmlspecialchars($params['prefix_title']),
				$required_label
			);
		}

		if (!empty($params['prefix_help'])) {
			$out .= sprintf('<dd class="help">%s</dd>',
				htmlspecialchars($params['prefix_help'])
			);
		}

		$label = sprintf('<label for="%s">%s</label>', $attributes['id'], $label);

		if ($type == 'radio' || $type == 'checkbox') {
			$out .= sprintf('<dd>%s %s', $input, $label);

			if (isset($help)) {
				$out .= sprintf(' <em class="help">(%s)</em>', htmlspecialchars($help));
			}

			$out .= '</dd>';
		}
		else {
			$out .= sprintf('<dt>%s%s</dt><dd>%s</dd>', $label, $required_label, $input);

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
		if (isset($params['shape']) && isset($params['html']) && !$params['html']) {
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
		if (!isset($params['target']) && ($dialog = Utils::getDialogTarget())) {
			$href .= (strpos($href, '?') === false ? '?' : '&') . '_dialog=' . $dialog;
		}

		if (!isset($params['class'])) {
			$params['class'] = '';
		}

		unset($params['href'], $params['label'], $params['prefix']);

		array_walk($params, function (&$v, $k) {
			$v = sprintf('%s="%s"', $k, htmlspecialchars((string)$v));
		});

		$params = implode(' ', $params);

		$label = (string)$label;
		$label = $label === '' ? '' : sprintf('<span>%s</span>', htmlspecialchars((string) $label));

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
		$suffix = $params['suffix'] ?? (!empty($params['table']) ? '_export=' : 'export=');

		$url = str_replace([$suffix . 'csv', $suffix . 'ods', $suffix . 'xlsx'], '', $url);
		$url = rtrim($url, '?&');

		if (false !== strpos($url, '?')) {
			$url .= '&';
		}
		else {
			$url .= '?';
		}

		$url .= $suffix;

		if (!empty($params['form'])) {
			$name = $params['name'] ?? 'export';
			$out = self::button(['value' => 'csv', 'shape' => 'export', 'label' => 'Export CSV', 'name' => $name, 'type' => 'submit']);
			$out .= self::button(['value' => 'ods', 'shape' => 'export', 'label' => 'Export LibreOffice', 'name' => $name, 'type' => 'submit']);
			$out .= self::button(['value' => 'xlsx', 'shape' => 'export', 'label' => 'Export Excel', 'name' => $name, 'type' => 'submit']);
		}
		else {
			$out  = self::linkButton(['href' => $url . 'csv', 'label' => 'Export CSV', 'shape' => 'export']);
			$out .= ' ' . self::linkButton(['href' => $url . 'ods', 'label' => 'Export LibreOffice', 'shape' => 'export']);
			$out .= ' ' . self::linkButton(['href' => $url . 'xlsx', 'label' => 'Export Excel', 'shape' => 'export']);
		}

		unset($params['table'], $params['suffix']);
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
			return sprintf('<dt>%s</dt><dd>%s</dd>', htmlspecialchars($field->label), $v ?: '<em>Non renseigné</em>');
		}

		if (isset($params['session']) && !$params['session']->canAccess(Session::SECTION_USERS, $field->management_access_level)) {
			return '';
		}

		$params = [
			'type'     => $type,
			'name'     => $name,
			'label'    => $field->label,
			'source'   => $source,
			'disabled' => !empty($params['disabled']),
			'required' => $field->required,
			'help'     => $field->help,
			// Fix for autocomplete
			'autocomplete' => 'off',
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
			$has_title = false;
			$out = '';

			foreach ($options as $k => $v)
			{
				$b = 0x01 << (int)$k;

				$p = [
					'type'    => 'checkbox',
					'label'   => $v,
					'value'   => $v,
					'default' => ($value & $b) ? $v : null,
					'name'    => sprintf('%s[%d]', $name, $k),
				];

				if (!$has_title) {
					$has_title = true;
					$p['prefix_title'] = $field->label;
					$p['prefix_help'] = $field->help;
					$p['prefix_required'] = $field->required;
				}

				$out .= CommonFunctions::input($p);
			}

			return $out;
		}
		elseif ($type === 'select') {
			$params['options'] = array_combine($field->options, $field->options);
			$params['default_empty'] = '—';
		}
		elseif ($type === 'country') {
			$params['default'] ??= Config::getInstance()->get('country');
		}
		elseif ($type === 'checkbox') {
			$params['value'] = 1;
			$params['label'] = 'Oui';
			$params['prefix_title'] = $field->label;
		}
		elseif ($field->isNumber() && $field->type === 'number' && $context === 'admin_new') {
			$params['default'] = Users::getNewNumber();
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
			$params['datalist'] = $field->options ?? [];
			$params['type'] = 'text';
		}

		if ($field->system & $field::AUTOCOMPLETE && $field->name === 'adresse') {
			$params['datalist'] = 'address';
			$params['data-default-country'] = Config::getInstance()->get('country');
		}

		if ($context === 'admin_new') {
			if ($field->default_value === 'NOW()') {
				$params['default'] = new \DateTime;
			}
			elseif (!empty($field->default_value)) {
				$params['default'] = $field->default_value;
			}
		}

		$out = CommonFunctions::input($params);

		if (($context === 'admin_new' || $context === 'admin_edit') && $field->system & $field::LOGIN) {
			$out .= '<dd class="help"><small>(Sera utilisé comme identifiant de connexion si le membre a le droit de se connecter.)</small></dd>';
		}

		if ($context === 'admin_new'
			&& $field->isNumber()
			&& $field->type === 'number') {
			$out .= '<dd class="help"><small>Doit être unique, laisser vide pour que le numéro soit attribué automatiquement.</small></dd>';
		}
		elseif (($context === 'admin_edit' || $context === 'admin_new')
			&& $field->isNumber()) {
			$out .= '<dd class="help"><small>Doit être unique pour chaque membre.</small></dd>';
		}

		return $out;
	}

	static public function user_field(array $params): string
	{
		if (isset($params['field'])) {
			$field = $params['field'];
			$name = $field->name;
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

			$files = json_decode($v, true);

			if (empty($files)) {
				return '';
			}

			$count = 0;
			$label = '';
			$thumb = '';

			foreach ($files as $path) {
				$file = Files::get($path);

				if (!$file->hasThumbnail()) {
					$count++;
					continue;
				}
				elseif ($thumb !== '') {
					$count++;
					continue;
				}

				$thumb = sprintf(
					'<a href="%s"><img src="%s" alt="%s" /></a>',
					Utils::getLocalURL($params['files_href']),
					htmlspecialchars($file->thumb_url()),
					htmlspecialchars($field->label)
				);
			}

			if ($count) {
				$label = ($count != count($files) ? '+' : '')
					. ($count == 1 ? '1 fichier' : $count . ' fichiers');
				$label = '<figcaption><a href="' . Utils::getLocalURL($params['files_href']) . '">' . $label . '</a></figcaption>';
			}

			if ($thumb !== '') {
				$out = '<div class="files-list"><figure>' . $thumb . $label . '</figure></div>';
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
		elseif ($field->type === 'month') {
			$date = \DateTime::createFromFormat('!Y-m', $v);
			$out = Utils::strftime_fr($date, '%B %Y');
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

	static public function dropdown(array $params): string
	{
		if (!isset($params['options'], $params['title'])) {
			throw new \InvalidArgumentException('Missing parameter for "dropdown"');
		}

		$out = sprintf('<nav class="dropdown" aria-role="listbox" aria-expanded="false" tabindex="0" title="%s"><ul>',
			htmlspecialchars($params['title']));

		foreach ($params['options'] as $option) {
			$selected = '';
			$link = '';
			$aside = '';
			$content = $option['html'] ?? ($option['label'] ?? null);

			if (null === $content) {
				throw new \InvalidArgumentException('dropdown: missing "html" or "label" parameter for option: ' . json_encode($option));
			}

			if (isset($option['aside'])) {
				$aside = sprintf('<small>%s</small>', htmlspecialchars($option['aside']));
			}

			if (isset($option['value']) && $option['value'] == $params['value']) {
				$selected = 'aria-selected="true" class="selected"';
			}

			if (isset($option['href'])) {
				$content = sprintf('<a href="%s"><strong>%s</strong> %s</a>', htmlspecialchars($option['href']), $content, $aside);
				$aside = '';
			}

			$out .= sprintf('<li %s aria-role="option">%s%s</li>', $selected, $content, $aside);
		}

		$out .= '</ul></nav>';
		return $out;
	}

	const TAG_PRESETS = [
		'debt' => ['Dette', 'DarkSalmon'],
		'credit' => ['Créance', 'DarkKhaki'],
		'overdraft' => ['Découvert', 'darkred'],
		'anomaly' => ['Anomalie', 'darkred'],
		'reconciliation_required' => ['À rapprocher', 'indianred'],
		'reconciled' => ['Rapproché', '#999'],
		'closed' => ['Clôturé', '#999'],
		'locked' => ['Verrouillé', 'indianred'],
		'open' => ['En cours', 'darkgreen'],
	];

	static public function tag(array $params): string
	{
		if (!empty($params['preset'])) {
			$p = $params['preset'];
			$params['label'] = self::TAG_PRESETS[$p][0];
			$params['color'] = self::TAG_PRESETS[$p][1];
		}

		return sprintf('<span class="tag%s" style="--tag-color: %s;">%s</span>',
			!empty($params['small']) ? ' small' : '',
			htmlspecialchars($params['color'] ?? '#999'),
			htmlspecialchars($params['label'] ?? '')
		);
	}
}
