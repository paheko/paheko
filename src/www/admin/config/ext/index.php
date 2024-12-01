<?php
namespace Paheko;

use Paheko\UserTemplate\Modules;
use Paheko\Extensions;
use Paheko\Plugins;
use Paheko\Users\Session;

require_once __DIR__ . '/../_inc.php';

$csrf_key = 'ext';

if (qg('install')) {
	foreach (Modules::refresh() as $error) {
		// Errors are not used currently
		$form->addError('Module ' . $error);
	}

	foreach (Plugins::refresh() as $error) {
		// Errors are not used currently
		$form->addError('Plugin ' . $error);
	}

	$list = Extensions::listDisabled();
	$tpl->assign('url_plugins', ENABLE_TECH_DETAILS ? WEBSITE . 'wiki?name=Extensions' : null);
	$tpl->assign('installable', true);
}
else {
	Modules::refreshEnabledModules();
	$list = Extensions::listEnabled();
	$tpl->assign('installable', false);
}

$url_help_modules = sprintf(HELP_PATTERN_URL, 'modules');
$tpl->assign(compact('list', 'csrf_key', 'url_help_modules'));

$tpl->display('config/ext/index.tpl');

flush();
Plugins::upgradeAllIfRequired();
