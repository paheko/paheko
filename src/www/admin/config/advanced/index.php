<?php
namespace Garradin;

use Garradin\Accounting\Years;

require_once __DIR__ . '/../_inc.php';

$form->runIf('reset_ok', function () use ($session) {
	Install::reset($session, f('passe_verif'));
}, 'reset', Utils::getSelfURI(['msg' => 'RESET']));

$form->runIf('reopen_ok', function () use ($session) {
	$year = Years::get((int) f('year'));
	$year->reopen($session->getUser()->id);
}, 'reopen_year', Utils::getSelfURI(['msg' => 'REOPEN']));

$tpl->assign('closed_years', Years::listClosedAssoc());

$tpl->display('admin/config/advanced/index.tpl');
