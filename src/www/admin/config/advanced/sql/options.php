<?php
namespace Paheko;

use Paheko\Users\Session;

require_once __DIR__ . '/../../_inc.php';

if (isset($_GET['profiler'])) {
	Utils::setProfilerCookie((bool)$_GET['profiler']);
	Utils::redirect('./options.php');
}

$tpl->assign('has_profiler', Utils::hasProfilerCookie());

$tpl->display('config/advanced/sql/options.tpl');
