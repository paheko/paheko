<?php
namespace Paheko;

use Paheko\Services\Subscriptions;
use Paheko\Accounting\Reports;
use Paheko\Entities\Accounting\Account;
use Paheko\UserTemplate\Modules;

require_once __DIR__ . '/_inc.php';

$tpl->assign('membre', $user);

$list = Subscriptions::perUserList($user->id);
$list->loadFromQueryString();

$tpl->assign(compact('list'));

$services = Subscriptions::listDistinctForUser($user->id);
$accounts = Reports::getAccountsBalances(['user' => $user->id, 'type' => Account::TYPE_THIRD_PARTY]);

$variables = compact('list', 'services', 'accounts');
$tpl->assign($variables);
$tpl->assign('snippets', Modules::snippetsAsString(Modules::SNIPPET_MY_SERVICES, $variables));

$tpl->display('me/services.tpl');
