<?php
namespace Paheko;

use Paheko\Users\DynamicFields;
use Paheko\Users\Session;

require_once __DIR__ . '/_inc.php';

$user = Session::getInstance()->user();
$csrf_key = 'edit_my_info';
$can_edit = $user->canEditOneField();

if (!$user->canEditOneField()) {
	throw new UserException('Vous ne pouvez modifier aucun champ de votre fiche membre, merci de contacter un⋅e administrateur⋅trice pour modifier votre fiche de membre.');
}

$form->runIf('save', function () use ($user) {
	$user->importForm();
	$user->checkLoginFieldForUserEdit();
	$user->save();
}, $csrf_key, '!me/?ok');

$fields = DynamicFields::getInstance()->all();

$tpl->assign(compact('csrf_key', 'user', 'fields'));

$tpl->display('me/edit.tpl');
