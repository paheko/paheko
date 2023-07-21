<?php
declare(strict_types=1);

namespace Paheko;

use Paheko\Users\DynamicFields;

require_once __DIR__ . '/../_inc.php';

$csrf_key = 'change_fields_delete';
$fields = DynamicFields::getInstance();

$field = $fields->fieldById((int)qg('id'));

if (!$field) {
	throw new UserException('Le champ indiqué n\'existe pas.');
}

$form->runIf('delete', function () use ($field, $fields) {
	if (!f('confirm_delete')) {
		throw new UserException('Merci de bien vouloir cocher la case pour confirmer la suppression.');
	}

	$fields->delete($field->name);
	$fields->save();
}, $csrf_key, '!config/fields/?msg=DELETED');

$tpl->assign(compact('csrf_key', 'field'));

$tpl->display('config/fields/delete.tpl');
