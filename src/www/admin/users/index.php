<?php
namespace Garradin;

use Garradin\Users\Categories;
use Garradin\Users\Users;

require_once __DIR__ . '/_inc.php';

$current_cat = (int) qg('cat') ?: null;

$categories = [0 => '— Toutes (sauf cachées) —'];

// Remove hidden categories
if (!$session->canAccess($session::SECTION_USERS, $session::ACCESS_WRITE)) {
	$categories = array_merge($categories, Categories::listAssoc(Categories::WITHOUT_HIDDEN));
}
else {
	$categories[-1] = '— Toutes (même cachées) —';
	$categories = array_merge($categories, Categories::listAssoc());
}

// Deny access to hidden categories to users that are not admins
if (!array_key_exists($current_cat, $categories)) {
	$current_cat = null;
}

$can_edit = $session->canAccess($session::SECTION_USERS, $session::ACCESS_ADMIN);

$list = Users::listByCategory($current_cat);
$list->loadFromQueryString();

$tpl->assign(compact('can_edit', 'list', 'current_cat', 'categories'));

$tpl->display('users/index.tpl');
