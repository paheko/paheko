<?php
namespace Garradin;

use Garradin\Users\Categories;
use Garradin\Users\Users;

require_once __DIR__ . '/_inc.php';

$categories = Categories::listSimple();
$hidden_categories = Categories::listHidden();

$current_cat = (int) qg('cat') ?: null;

// Deny access to hidden categories to users that are not admins
if ($current_cat && !$session->canAccess($session::SECTION_USERS, $session::ACCESS_ADMIN) && array_key_exists($current_cat, $hidden_categories)) {
	$current_cat = null;
}

$can_edit = $session->canAccess($session::SECTION_USERS, $session::ACCESS_ADMIN);

$list = Users::listByCategory($current_cat);
$list->loadFromQueryString();

$tpl->assign('sent', null !== qg('sent'));

$tpl->assign(compact('can_edit', 'list', 'current_cat', 'hidden_categories', 'categories'));

$tpl->display('admin/membres/index.tpl');
