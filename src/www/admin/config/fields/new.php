<?php
namespace Garradin;

use Garradin\Entities\Users\DynamicField;
use Garradin\Users\DynamicFields;

require_once __DIR__ . '/../_inc.php';

$csrf_key = 'change_fields_new_' . (int)qg('id');
$fields = DynamicFields::getInstance();

$presets = $fields->getInstallablePresets();

// No presets left to install
if (!count($presets)) {
	Utils::redirect('!config/fields/edit.php');
}

$form->runIf('add', function () use ($fields) {
	$preset = f('preset');

	if (!$preset) {
		Utils::redirect('!config/fields/edit.php');
	}

	$field = $fields->installPreset(f('preset'));

	if (!$field->exists()) {
		$field->sort_order = $fields->getLastOrderIndex();
		$fields->add($field);
	}

	$fields->save();
}, $csrf_key, '!config/fields/?msg=SAVED');

$tpl->assign(compact('csrf_key', 'presets'));

$tpl->display('config/fields/new.tpl');
