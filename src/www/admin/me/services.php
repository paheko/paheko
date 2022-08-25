<?php
namespace Garradin;

use Garradin\Services\Services_User;
use Garradin\Accounting\Reports;
use Garradin\Entities\Accounting\Account;

require_once __DIR__ . '/_inc.php';

$tpl->assign('membre', $user);

$list = Services_User::perUserList($user->id);
$list->loadFromQueryString();

$tpl->assign(compact('list'));

$tpl->assign('services', Services_User::listDistinctForUser($user->id));
$tpl->assign('accounts', Reports::getAccountsBalances(['user' => $user->id, 'type' => Account::TYPE_THIRD_PARTY]));

$tpl->display('me/services.tpl');
