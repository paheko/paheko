<?php

namespace Paheko;

use Paheko\Users\Session;
use Paheko\Accounting\Years;

require_once __DIR__ . '/../_inc.php';

$session = Session::getInstance();

// ALLOW_ACCOUNTS_ACCESS is true when coming from the account selector only
if (!defined('Paheko\ALLOW_ACCOUNTS_ACCESS') || !ALLOW_ACCOUNTS_ACCESS) {
	$session->requireAccess($session::SECTION_ACCOUNTING, $session::ACCESS_READ);
}

$current_year = Years::getUserSelectedYear();

define('Paheko\CURRENT_YEAR_ID', $current_year ? $current_year->id() : null);

$tpl->assign('current_year', $current_year);
