<?php
namespace Paheko;

use Paheko\Accounting\ImportRules;
use Paheko\Users\Session;

require_once __DIR__ . '/../../_inc.php';

$session = Session::getInstance();
$session->requireAccess($session::SECTION_ACCOUNTING, $session::ACCESS_ADMIN);

$list = ImportRules::getList();
$tpl->assign(compact('list'));

$tpl->display('acc/accounts/rules/index.tpl');
