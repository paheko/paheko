<?php
namespace Paheko;

use Paheko\Users\Session;
use Paheko\Accounting\Years;

require_once __DIR__ . '/../_inc.php';

$years = new Years;
$url = f('from') ?: ADMIN_URL . 'acc/years/';
$csrf_key = 'year_select';

$form->runIf('switch', function () {
	$year = Years::get((int)f('switch'));

	if (!$year) {
		throw new UserException('Exercice inconnu');
	}

	$user = Session::getLoggedUser();
	$user->setPreference('accounting_year', $year->id());
	$user->save();
}, $csrf_key, $url);

$tpl->assign('years', $years->list(true));
$tpl->assign('from', qg('from'));
$tpl->assign('msg', qg('msg'));
$tpl->assign(compact('csrf_key'));

$tpl->display('acc/years/select.tpl');
