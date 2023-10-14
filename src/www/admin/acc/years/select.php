<?php
namespace Paheko;

use Paheko\Users\Session;
use Paheko\Accounting\Years;

require_once __DIR__ . '/../../_inc.php';

$years = new Years;

if (f('change')) {
	$year = Years::get(f('year'));

	if (!$year) {
		throw new UserException('Exercice inconnu');
	}

	$user = Session::getLoggedUser();
	$user->setPreference('accounting_year', $year->id());

	$session->save();
	Utils::redirect(f('from') ?: ADMIN_URL . 'acc/years/');
}

$tpl->assign('list', $years->listOpen());
$tpl->assign('from', qg('from'));

$tpl->display('acc/years/select.tpl');
