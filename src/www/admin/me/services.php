<?php
namespace Garradin;

use Garradin\Services\Services_User;
use Garradin\Accounting\Reports;
use Garradin\Entities\Accounting\Account;
use Garradin\UserTemplate\Modules;

require_once __DIR__ . '/_inc.php';

$tpl->assign('membre', $user);

$list = Services_User::perUserList($user->id);
$list->loadFromQueryString();

$tpl->assign(compact('list'));

$services = Services_User::listDistinctForUser($user->id);
$accounts = Reports::getAccountsBalances(['user' => $user->id, 'type' => Account::TYPE_THIRD_PARTY]);

$variables = compact('list', 'services', 'accounts');
$tpl->assign($variables);
$tpl->assign('snippets', Modules::snippetsAsString(Modules::SNIPPET_MY_SERVICES, $variables));

$tpl->display('me/services.tpl');
