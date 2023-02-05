<?php
namespace Garradin;

use Garradin\Users\Session;
use Garradin\Entities\Accounting\Chart;
use Garradin\UserTemplate\Modules;
use Garradin\Plugin;

const INSTALL_PROCESS = true;

require_once __DIR__ . '/../../include/test_required.php';
require_once __DIR__ . '/../../include/init.php';

if (file_exists(DB_FILE))
{
    throw new UserException('Garradin est déjà installé');
}

Install::checkAndCreateDirectories();
Install::checkReset();

function f($key)
{
    return \KD2\Form::get($key);
}

$tpl = Template::getInstance();
$tpl->assign('admin_url', ADMIN_URL);

$form = new Form;
$tpl->assign_by_ref('form', $form);

$form->runIf('save', function () {
    Install::installFromForm();
    Session::getInstance()->forceLogin(1);
}, 'install', ADMIN_URL);

$tpl->assign('countries', Chart::COUNTRY_LIST);

$modules = Modules::listLocal();
$plugins = Plugin::listDownloaded(false);

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

ksort($installable);

$tpl->assign('installable', $installable);

$tpl->display('install.tpl');
