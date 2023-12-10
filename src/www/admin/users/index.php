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

$can_edit = $session->canAccess($session::SECTION_USERS, $session::ACCESS_ADMIN);

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

$tpl->assign(compact('can_edit', 'list', 'current_cat', 'categories', 'title'));

$tpl->display('users/index.tpl');
