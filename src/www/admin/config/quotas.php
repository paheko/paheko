<?php
namespace Paheko;

use Paheko\Files\Files;
use Paheko\Backup;

require_once __DIR__ . '/_inc.php';

$config = Config::getInstance();

$sizes = [
	'quota_used' => Files::getUsedQuota(),
	'quota_max'  => Files::getQuota(),
	'quota_left' => Files::getRemainingQuota(),
	'contexts'   => Files::getContextsQuotas(),
	'db_backups' => Backup::getAllBackupsTotalSize(),
	'db'         => Backup::getDBSize(),
];

$sizes['db_total'] = $sizes['db'] + $sizes['db_backups'];
$tpl->assign($sizes);

$tpl->display('config/quotas.tpl');
