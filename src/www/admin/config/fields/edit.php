<?php
namespace Paheko;

use Paheko\Entities\Users\DynamicField;
use Paheko\Users\DynamicFields;

require_once __DIR__ . '/../_inc.php';

$csrf_key = 'change_fields_edit_' . (int)qg('id');
$fields = DynamicFields::getInstance();

if (qg('id')) {
	$field = $fields->fieldById((int)qg('id'));
}
else {
	$field = new DynamicField;
}

if (!$field) {
	throw new UserException('Le champ indiqué n\'existe pas.');
}

$form->runIf('save', function () use ($field, $fields) {
	$field->importForm();

	if (!$field->exists()) {
		$field->sort_order = $fields->getLastOrderIndex();
		$fields->add($field);
	}

	$fields->save();
}, $csrf_key, '!config/fields/?msg=SAVED');

$tpl->assign(compact('csrf_key', 'field'));

$tpl->display('config/fields/edit.tpl');
