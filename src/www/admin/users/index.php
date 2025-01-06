<?php
namespace Paheko;

use Paheko\Users\Categories;
use Paheko\Users\Session;
use Paheko\Users\Users;

require_once __DIR__ . '/_inc.php';

$current_cat = (int) qg('cat');

if ($format = qg('export')) {
	Session::getInstance()->requireAccess($session::SECTION_USERS, $session::ACCESS_ADMIN);
	Users::exportCategory($format, $current_cat);
	return;
}

$is_manager = $session->canAccess($session::SECTION_USERS, $session::ACCESS_WRITE);
$categories = Categories::listAssocWithStats($is_manager ? null : Categories::WITHOUT_HIDDEN);

// Deny access to hidden categories to users that are not admins
if (!array_key_exists($current_cat, $categories)) {
	$current_cat = null;
}

$can_check = $session->canAccess($session::SECTION_USERS, $session::ACCESS_WRITE);

$list = Users::listByCategory($current_cat, $session);
$list->loadFromQueryString();

if (!$current_cat) {
	$title = 'Liste des membres';
}
elseif ($current_cat == -1) {
	$title = 'Tous les membres';
}
else {
	$title = sprintf('Liste des membres — %s', $categories[$current_cat]->label ?? '');
}

$categories_list = [];

foreach ($categories as $id => $category) {
	$categories_list[] = [
		'label' => $category->label,
		'value' => $id,
		'href' => '?cat=' . $id,
		'aside' => ngettext('%n membre', '%n membres', $category->count),
	];
}

$tpl->assign(compact('can_check', 'list', 'current_cat', 'categories_list', 'title'));

$tpl->display('users/index.tpl');
