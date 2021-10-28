<?php
namespace Garradin;

use Garradin\Accounting\Years;
use Garradin\Files\Files;

require_once __DIR__ . '/../_inc.php';

$quota_used = Files::getUsedQuota(true);

$form->runIf('reset_ok', function () use ($session) {
	Install::reset($session, f('passe_verif'));
}, 'reset', Utils::getSelfURI(['msg' => 'RESET']));

$form->runIf('reopen_ok', function () use ($session) {
	$year = Years::get((int) f('year'));
	$year->reopen($session->getUser()->id);
}, 'reopen_year', Utils::getSelfURI(['msg' => 'REOPEN']));

if (FILE_STORAGE_BACKEND !== 'SQLite' && ENABLE_TECH_DETAILS) {
	$form->runIf('migrate_backend_ok', function () {
		Files::migrateStorage('SQLite', FILE_STORAGE_BACKEND, null, FILE_STORAGE_CONFIG);
		Files::truncateStorage('SQLite');
	}, 'migrate_backend', Utils::getSelfURI(['msg' => 'MIGRATION_OK']));

	$form->runIf('migrate_back_ok', function () {
		Files::migrateStorage(FILE_STORAGE_BACKEND, 'SQLite', FILE_STORAGE_CONFIG, null);
	}, 'migrate_back', Utils::getSelfURI(['msg' => 'MIGRATION_OK']));
}

$tpl->assign('closed_years', Years::listClosedAssoc());
$tpl->assign('quota_used', $quota_used);
$tpl->assign('storage_backend', FILE_STORAGE_BACKEND);

$tpl->display('admin/config/advanced/index.tpl');
