<?php
namespace Paheko;

use Paheko\Config;
use Paheko\Files\Files;
use Paheko\Entities\Files\File;

require_once __DIR__ . '/../_inc.php';

$csrf_key = 'versions_config';

$form->runIf('save', function () {
	$config = Config::getInstance();
	$config->importForm();
	$config->save();
}, $csrf_key, '!config/backup/versions.php?msg=CONFIG_SAVED');

$versioning_policies = Config::VERSIONING_POLICIES;
$disk_use = Files::getContextDiskUsage(File::CONTEXT_VERSIONS);

$tpl->assign(compact('csrf_key', 'versioning_policies', 'disk_use'));

$tpl->display('config/backup/versions.tpl');
