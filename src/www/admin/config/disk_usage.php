<?php
namespace Paheko;

use Paheko\Files\Files;
use Paheko\Backup;

require_once __DIR__ . '/_inc.php';

$csrf_key = 'disk_usage';
$versioning_policy = Files::getVersioningPolicy();

$sizes = [
	'quota_used' => Files::getUsedQuota(),
	'quota_max'  => Files::getQuota(),
	'quota_left' => Files::getRemainingQuota(),
	'contexts'   => Files::getContextsDiskUsage(),
	'db_backups' => Backup::getAllBackupsTotalSize(),
	'db'         => Backup::getDBSize(),
];

$sizes['db_total'] = $sizes['db'] + $sizes['db_backups'];
$tpl->assign($sizes);

$tpl->assign(compact('csrf_key', 'versioning_policy'));

$tpl->display('config/disk_usage.tpl');
