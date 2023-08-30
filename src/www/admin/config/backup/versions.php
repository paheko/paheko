<?php
namespace Paheko;

use Paheko\Config;
use Paheko\Files\Files;
use Paheko\Entities\Files\File;

require_once __DIR__ . '/../_inc.php';

$csrf_key = 'versions_config';
$versioning_policy = Files::getVersioningPolicy();

$form->runIf('save', function () {
	$config = Config::getInstance();
	$config->importForm();
	$config->save();
}, $csrf_key, '!config/backup/versions.php?msg=CONFIG_SAVED');


$form->runIf('prune_versions', function() use ($versioning_policy) {
	if ('none' === $versioning_policy) {
		throw new UserException('Le versionnement des fichiers est désactivé.');
	}

	Files::pruneOldVersions();
}, $csrf_key, '!config/backup/versions.php?msg=PRUNED');

$form->runIf('delete', function() use ($versioning_policy) {
	if ('none' !== $versioning_policy) {
		throw new UserException('Le versionnement des fichiers n\'est pas désactivé.');
	}

	Files::deleteAllVersions();
}, $csrf_key, '!config/backup/versions.php?msg=DELETED');

$versioning_policies = Config::VERSIONING_POLICIES;
$disk_use = Files::getContextDiskUsage(File::CONTEXT_VERSIONS);

$tpl->assign(compact('csrf_key', 'versioning_policies', 'disk_use'));

if (isset($_GET['delete_versions'])) {
	$tpl->display('config/backup/versions_delete.tpl');
}
else {
	$tpl->display('config/backup/versions.tpl');
}
