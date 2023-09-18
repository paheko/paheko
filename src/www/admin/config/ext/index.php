<?php
namespace Paheko;

use Paheko\UserTemplate\Modules;
use Paheko\Extensions;
use Paheko\Plugins;
use Paheko\Users\Session;

require_once __DIR__ . '/../_inc.php';

$csrf_key = 'ext';
$session = Session::getInstance();

$form->runIf(f('enable') !== null || f('disable') !== null, function () {
	$ext = f('enable') ?? f('disable');

	if (!is_array($ext)) {
		throw new UserException('Unknown action.');
	}

	$enabled = f('enable') ? true : false;
	$type = key($ext);
	$name = current($ext);

	Extensions::toggle($type, $name, $enabled);
	Utils::redirect('!config/ext/?focus=' . $name);
}, $csrf_key);

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
