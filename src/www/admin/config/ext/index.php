<?php
namespace Garradin;

use Garradin\UserTemplate\Modules;
use Garradin\Plugins;
use Garradin\Users\Session;

require_once __DIR__ . '/../_inc.php';

$csrf_key = 'ext';
$session = Session::getInstance();

$form->runIf('install', function () {
	Plugins::install(f('install'));
}, $csrf_key, '!config/ext/?focus=' . f('install'));

$form->runIf('enable', function () {
	$m = Modules::get(f('enable'));

	if (!$m) {
		throw new UserException('Ce module n\'existe pas');
	}

	$m->enabled = true;
	$m->save();
}, $csrf_key, '!config/ext/?focus=' . f('enable'));

$form->runIf('disable_module', function () {
	$m = Modules::get(f('disable_module'));

	if (!$m) {
		throw new UserException('Ce module n\'existe pas');
	}

	$m->enabled = false;
	$m->save();
}, $csrf_key, '!config/ext/');

$form->runIf('disable_plugin', function () {
	$p = Plugins::get(f('disable_plugin'));

	if (!$p) {
		throw new UserException('Cette extension n\'existe pas');
	}

	$p->set('enabled', false);
	$p->save();
}, $csrf_key, '!config/ext/');

Modules::refresh();

if (qg('install')) {
	$list = Plugins::listModulesAndPlugins(true);
	$tpl->assign('url_plugins', ENABLE_TECH_DETAILS ? WEBSITE . 'wiki?name=Extensions' : null);
	$tpl->assign('installable', true);
}
else {
	$list = Plugins::listModulesAndPlugins(false);
	$tpl->assign('installable', false);
}

$url_help_modules = sprintf(HELP_PATTERN_URL, 'modules');
$tpl->assign(compact('list', 'csrf_key', 'url_help_modules'));

$tpl->display('config/ext/index.tpl');

flush();
Plugins::upgradeAllIfRequired();
