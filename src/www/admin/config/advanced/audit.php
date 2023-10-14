<?php
namespace Paheko;

use Paheko\Log;
use Paheko\Users\Session;

require_once __DIR__ . '/../_inc.php';

$list = Log::list();
$list->loadFromQueryString();

$tpl->assign(compact('list'));

$tpl->display('config/advanced/audit.tpl');
