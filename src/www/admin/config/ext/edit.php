<?php
namespace Paheko;

use Paheko\UserTemplate\Modules;
use Paheko\Users\Session;

require_once __DIR__ . '/../_inc.php';

$module = Modules::get(qg('module'));

if (!$module) {
	throw new UserException('Module inconnu');
}

if (null !== qg('export')) {
	$module->export(Session::getInstance());
	return;
}

$path = qg('p');
$parent_path_uri = rawurlencode($module->path($path));
$list = $module->listFiles($path);

$url_help_modules = sprintf(HELP_PATTERN_URL, 'modules');
$tpl->assign(compact('list', 'url_help_modules', 'module', 'path', 'parent_path_uri'));

$tpl->display('config/ext/edit.tpl');
