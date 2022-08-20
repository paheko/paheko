<?php
namespace Garradin;

use Garradin\UserTemplate\UserForms;

require_once __DIR__ . '/../_inc.php';

$form->runIf(qg('enable') !== null, function () {
	$uf = UserForms::get(qg('enable'));

	if (!$uf) {
		throw new UserException('Ce formulaire n\'existe pas');
	}

	$uf->enabled = true;
	$uf->save();
}, null, '!config/forms/');

$form->runIf(qg('disable') !== null, function () {
	$uf = UserForms::get(qg('disable'));

	if (!$uf) {
		throw new UserException('Ce formulaire n\'existe pas');
	}

	$uf->enabled = false;
	$uf->save();
}, null, '!config/forms/');

UserForms::refresh();

$list = UserForms::list();

$tpl->assign(compact('list'));

$tpl->display('config/forms/index.tpl');
