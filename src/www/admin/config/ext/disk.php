<?php
namespace Paheko;

use Paheko\Extensions;
use Paheko\Users\Session;

require_once __DIR__ . '/../_inc.php';

$ext = Extensions::get(qg('name'));

if (!$ext) {
	throw new UserException('Extension inconnue');
}

if ($ext->type !== 'module') {
	throw new UserException('Invalid extension: only modules allow to see disk space');
}

$module = $ext->module;
$tpl->assign(compact('ext', 'module'));

$tpl->display('config/ext/disk.tpl');
