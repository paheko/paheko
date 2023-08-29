<?php
namespace Paheko;

use Paheko\Files\Files;
use Paheko\Backup;

require_once __DIR__ . '/_inc.php';

$csrf_key = 'quotas';
$versioning_policy = Files::getVersioningPolicy();

$form->runIf('prune_versions', function() use ($versioning_policy) {
	if ('none' === $versioning_policy) {
		throw new UserException('Le versionnement des fichiers est désactivé.');
	}

	Files::pruneOldVersions();
}, $csrf_key, '!config/quotas.php?msg=PRUNED');

$form->runIf('delete', function() use ($versioning_policy) {
	if ('none' !== $versioning_policy) {
		throw new UserException('Le versionnement des fichiers n\'est pas désactivé.');
	}

	Files::deleteAllVersions();
}, $csrf_key, '!config/quotas.php?msg=DELETED');

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

$tpl->assign(compact('csrf_key', 'versioning_policy'));

if (isset($_GET['prune_versions'])) {
	$tpl->display('config/quotas_versions_delete.tpl');
}
else {
	$tpl->display('config/quotas.tpl');
}
