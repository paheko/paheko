<?php
namespace Garradin;

use Garradin\UserTemplate\Modules;

require_once __DIR__ . '/../_inc.php';

$form->runIf(qg('enable') !== null, function () {
	$m = Modules::get(qg('enable'));

	if (!$m) {
		throw new UserException('Ce module n\'existe pas');
	}

	$m->enabled = true;
	$m->save();
}, null, '!config/modules/');

$form->runIf(qg('disable') !== null, function () {
	$m = Modules::get(qg('disable'));

	if (!$m) {
		throw new UserException('Ce module n\'existe pas');
	}

	$m->enabled = false;
	$m->save();
}, null, '!config/modules/');

Modules::refresh();

$list = Modules::list();

$tpl->assign(compact('list'));

$tpl->display('config/modules/index.tpl');
