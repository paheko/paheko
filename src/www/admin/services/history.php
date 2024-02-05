<?php

namespace Paheko;

use Paheko\Services\Subscriptions;

require_once __DIR__ . '/_inc.php';

$list = Subscriptions::getList();
$list->loadFromQueryString();

$tpl->assign(compact('list'));

$tpl->display('services/history.tpl');
