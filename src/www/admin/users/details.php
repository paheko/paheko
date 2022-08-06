<?php
namespace Garradin;

use Garradin\Accounting\Transactions;
use Garradin\Services\Services_User;
use Garradin\Users\Categories;
use Garradin\Users\Users;

require_once __DIR__ . '/_inc.php';

$user = Users::get((int) qg('id'));

if (!$user) {
	throw new UserException("Ce membre n'existe pas.");
}

$category = $user->category();

$services = Services_User::listDistinctForUser($user->id);

if ($session->canAccess($session::SECTION_ACCOUNTING, $session::ACCESS_READ)) {
	$tpl->assign('transactions_linked', Transactions::countForUser($user->id));
	$tpl->assign('transactions_created', Transactions::countForCreator($user->id));
}

$parent_name = $user->getParentName();
$children = $user->listChildren();

$tpl->assign(compact('services', 'user', 'category', 'children', 'parent_name'));

$tpl->display('users/details.tpl');
