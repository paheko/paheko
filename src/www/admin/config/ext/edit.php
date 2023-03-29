<?php
namespace Garradin;

use Garradin\UserTemplate\Modules;
use Garradin\Users\Session;

require_once __DIR__ . '/../_inc.php';

$module = Modules::get(qg('module'));

if (!$module) {
	throw new UserException('Module inconnu');
}

$csrf_key = 'ext_edit_' . $module->name;
$session = Session::getInstance();

$form->runIf('reset', function () use ($module) {
	$module->reset();
}, $csrf_key, '!config/ext/edit.php?module=' . $module->name);

$path = qg('p');
$list = $module->listFiles($path);

$url_help_modules = sprintf(HELP_PATTERN_URL, 'modules');
$tpl->assign(compact('list', 'csrf_key', 'url_help_modules', 'module', 'path'));

$tpl->display('config/ext/edit.tpl');
