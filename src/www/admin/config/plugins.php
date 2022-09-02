<?php

namespace Garradin;

use Garradin\Users\Session;

require_once __DIR__ . '/_inc.php';

$session = Session::getInstance();
$csrf_key = 'plugins';

$form->runIf('install', function ()  use ($session) {
	Plugin::install(f('plugin'), false);
	$session->set('plugins_menu', null);
}, $csrf_key, '!config/plugins.php');

$form->runIf('delete', function () use ($session) {
	$plugin = new Plugin(qg('delete'));
	$plugin->uninstall();
	$session->set('plugins_menu', null);
}, $csrf_key, '!config/plugins.php');

if (qg('delete')) {
	$plugin = new Plugin(qg('delete'));
	$tpl->assign('plugin', $plugin->getInfos());
	$tpl->assign('delete', true);
}
else {
	$tpl->assign('list_available', Plugin::listDownloaded());
	$tpl->assign('list_installed', Plugin::listInstalled());
}

$tpl->assign('garradin_website', WEBSITE);
$tpl->assign(compact('csrf_key'));

$tpl->display('config/plugins.tpl');

Plugin::upgradeAllIfRequired();
