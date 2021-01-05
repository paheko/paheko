<?php
namespace Garradin;

require_once __DIR__ . '/_inc.php';

$cats = new Membres\Categories;

$categories = $cats->listSimple();
$hidden_categories = $cats->listHidden();

$current_cat = (int) qg('cat') ?: null;

// Deny access to hidden categories to users that are not admins
if ($current_cat && !$session->canAccess('membres', Membres::DROIT_ADMIN) && array_key_exists($current_cat, $hidden_categories)) {
	$current_cat = null;
}

$can_edit = $session->canAccess('membres', Membres::DROIT_ADMIN);

$list = $membres->listByCategory($current_cat);
$list->loadFromQueryString();

$tpl->assign('sent', null !== qg('sent'));

$id_field = Config::getInstance()->get('champ_identite');

$tpl->assign(compact('can_edit', 'list', 'current_cat', 'hidden_categories', 'categories', 'id_field'));

$tpl->display('admin/membres/index.tpl');
