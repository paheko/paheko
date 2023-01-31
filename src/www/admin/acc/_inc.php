<?php

namespace Garradin;

use Garradin\Users\Session;
use Garradin\Accounting\Years;

require_once __DIR__ . '/../_inc.php';

$session = Session::getInstance();

// ALLOW_ACCOUNTS_ACCESS is true when coming from the account selector only
if (!defined('Garradin\ALLOW_ACCOUNTS_ACCESS') || !ALLOW_ACCOUNTS_ACCESS) {
	$session->requireAccess($session::SECTION_ACCOUNTING, $session::ACCESS_READ);
}

$user = Session::getLoggedUser();
$user_year = $user->getPreference('accounting_year');

if (!empty($_GET['set_year'])) {
	$user->setPreference('accounting_year', (int)$_GET['set_year']);
}

$current_year = null;

// Apply user year
if ($user_year) {
	// Check that the selected year is still valid
	$current_year = Years::get($user_year);

	if (!$current_year || $current_year->closed) {
		$current_year = null;
		$user->setPreference('accounting_year', null);
	}
}

// Or just select the first open year
if (!$current_year) {
	$current_year = Years::getCurrentOpenYear();
}

define('Garradin\CURRENT_YEAR_ID', $current_year ? $current_year->id() : null);

$tpl->assign('current_year', $current_year);
