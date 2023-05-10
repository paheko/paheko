<?php
namespace Garradin;

use Garradin\Users\Categories;
use Garradin\Users\Session;
use Garradin\Users\Users;

require_once __DIR__ . '/_inc.php';

$current_cat = (int) qg('cat');

if ($format = qg('export')) {
	Session::getInstance()->requireAccess($session::SECTION_USERS, $session::ACCESS_ADMIN);
	Users::exportCategory($format, $current_cat);
	return;
}

$categories = [0 => '— Toutes (sauf cachées) —'];

// Remove hidden categories
if (!$session->canAccess($session::SECTION_USERS, $session::ACCESS_WRITE)) {
	$categories = $categories + Categories::listAssoc(Categories::WITHOUT_HIDDEN);
}
else {
	$categories[-1] = '— Toutes (même cachées) —';
	$categories = $categories + Categories::listAssoc();
}

// Deny access to hidden categories to users that are not admins
if (!array_key_exists($current_cat, $categories)) {
	$current_cat = null;
}

$can_edit = $session->canAccess($session::SECTION_USERS, $session::ACCESS_ADMIN);

$list = Users::listByCategory($current_cat);
$list->loadFromQueryString();

if (!$current_cat) {
	$title = 'Liste des membres';
}
elseif ($current_cat == -1) {
	$title = 'Tous les membres';
}
else {
	$title = sprintf('Liste des membres — %s', $categories[$current_cat] ?? '');
}

$tpl->assign(compact('can_edit', 'list', 'current_cat', 'categories', 'title'));

$tpl->display('users/index.tpl');
