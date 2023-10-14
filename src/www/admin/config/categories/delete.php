<?php
namespace Paheko;

use Paheko\Users\Categories;

require_once __DIR__ . '/../_inc.php';

$cat = Categories::get((int) qg('id'));

if (!$cat) {
	throw new UserException("Cette catégorie n'existe pas.");
}

$user = $session->getUser();

$csrf_key = 'cat_delete_' . $cat->id();

if ($cat->id() == $user->id_category) {
	throw new UserException("Vous ne pouvez pas supprimer votre catégorie.");
}

$form->runIf('delete', function () use($cat) {
	$cat->delete();
}, $csrf_key, '!config/categories/');

$tpl->assign(compact('cat', 'csrf_key'));

$tpl->display('config/categories/delete.tpl');
