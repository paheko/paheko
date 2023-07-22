<?php
namespace Paheko;

use Paheko\UserTemplate\Modules;

require_once __DIR__ . '/../_inc.php';

$module = Modules::get(qg('module'));

if (!$module) {
	throw new UserException('Module inconnu');
}

$path = qg('p');

$tpl->assign([
	'local' => $module->fetchLocalFile($path),
	'dist' => $module->fetchDistFile($path),
]);

$tpl->display('config/ext/diff.tpl');
