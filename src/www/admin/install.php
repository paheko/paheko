<?php
namespace Paheko;

use Paheko\Users\Session;
use Paheko\Entities\Accounting\Chart;
use Paheko\UserTemplate\Modules;
use Paheko\Plugins;

const INSTALL_PROCESS = true;

require_once __DIR__ . '/../../include/test_required.php';
require_once __DIR__ . '/../../include/init.php';

$exists = file_exists(DB_FILE);

if ($exists && !filesize(DB_FILE)) {
	@unlink(DB_FILE);
	$exists = false;
}

if ($exists) {
	throw new UserException('Paheko est déjà installé');
}

Install::checkAndCreateDirectories();
Install::checkReset();

if (DISABLE_INSTALL_FORM) {
	throw new \RuntimeException('Install form has been disabled');
}

function f($key)
{
	return \KD2\Form::get($key);
}

$tpl = Template::getInstance();
$tpl->assign('admin_url', ADMIN_URL);

$form = new Form;
$tpl->assign_by_ref('form', $form);
$csrf_key = 'install';

$form->runIf('save', function () {
	Install::installFromForm();
	Session::getInstance()->forceLogin(1);
}, $csrf_key, ADMIN_URL);

$tpl->assign('countries', Chart::COUNTRY_LIST);

$modules = Modules::listLocal();
$plugins = Plugins::listInstallable(false);

$installable = [];

foreach (Install::DEFAULT_PLUGINS as $plugin) {
	if (array_key_exists($plugin, $plugins)) {
		$installable[$plugin] = ['plugin' => $plugins[$plugin]];
	}
}

foreach (Install::DEFAULT_MODULES as $module) {
	if (array_key_exists($module, $modules)) {
		$installable[$module] = ['module' => $modules[$module]];
	}
}

uasort($installable, function ($a, $b) {
	$name1 = $a['module']->label ?? $a['plugin']->label;
	$name2 = $b['module']->label ?? $b['plugin']->label;
	return strcasecmp($name1, $name2);
});

$tpl->assign(compact('csrf_key', 'installable'));

$tpl->display('install.tpl');
