<?php
namespace Garradin;

use Garradin\Accounting\Years;
use Garradin\Files\Files;
use Garradin\Files\Storage;

require_once __DIR__ . '/../_inc.php';

$form->runIf('reset_ok', function () use ($session) {
	Install::reset($session, f('passe_verif'));
}, 'reset');

$form->runIf('reopen_ok', function () use ($session) {
	$year = Years::get((int) f('year'));
	$year->reopen($session->getUser()->id);
}, 'reopen_year', Utils::getSelfURI(['msg' => 'REOPEN']));

if (FILE_STORAGE_BACKEND !== 'SQLite' && ENABLE_TECH_DETAILS) {
	$form->runIf('migrate_backend_ok', function () {
		Storage::migrate('SQLite', FILE_STORAGE_BACKEND, null, FILE_STORAGE_CONFIG);
	}, 'migrate_backend', Utils::getSelfURI(['msg' => 'MIGRATION_OK']));

	$form->runIf('migrate_back_ok', function () {
		Storage::migrate(FILE_STORAGE_BACKEND, 'SQLite', FILE_STORAGE_CONFIG, null);
	}, 'migrate_back', Utils::getSelfURI(['msg' => 'MIGRATION_OK']));
}

$tpl->assign('closed_years', Years::listClosedAssoc());
$tpl->assign('storage_backend', FILE_STORAGE_BACKEND);

$tpl->display('config/advanced/index.tpl');
