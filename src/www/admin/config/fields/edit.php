<?php
namespace Garradin;

use Garradin\Entities\Users\DynamicField;
use Garradin\Users\DynamicFields;

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

$form->runIf('save', function () use ($field) {
	$field->importForm();
	$field->save();
}, $csrf_key, '!config/fields/?msg=SAVED');

$tpl->assign(compact('csrf_key', 'field'));

$tpl->display('admin/config/fields/edit.tpl');
