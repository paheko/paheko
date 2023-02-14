<?php
namespace Garradin;

use Garradin\UserTemplate\Modules;
use Garradin\Plugins;

require_once __DIR__ . '/../_inc.php';

$csrf_key = 'ext_delete';
$plugin = $module = null;

if (qg('plugin')) {
	$plugin = Plugins::get(qg('plugin'));

	$form->runIf(f('delete') && f('confirm_delete'), function () use ($plugin) {
		$plugin->delete();
	}, $csrf_key, '!config/ext/');
}
else {
	$module = Modules::get(qg('module'));

	$form->runIf(f('delete') && f('confirm_delete'), function () use ($module) {
		$module->delete();
	}, $csrf_key, '!config/ext/');
}

$tpl->assign(compact('plugin', 'module', 'csrf_key'));

$tpl->display('config/ext/delete.tpl');
