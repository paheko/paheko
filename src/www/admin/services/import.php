<?php

namespace Paheko;

use Paheko\CSV_Custom;
use Paheko\Users\Session;
use Paheko\Users\Users;
use Paheko\Services\Subscriptions;

require_once __DIR__ . '/_inc.php';

$session = Session::getInstance();
$session->requireAccess($session::SECTION_USERS, $session::ACCESS_ADMIN);

$csrf_key = 'su_import';
$csv = new CSV_Custom($session, 'su_import');

$csv->setColumns(Subscriptions::listImportColumns());
$csv->setMandatoryColumns(Subscriptions::listMandatoryImportColumns());

$form->runIf('cancel', function() use ($csv) {
	$csv->clear();
}, $csrf_key, Utils::getSelfURI());

$form->runIf(f('load') && isset($_FILES['file']['tmp_name']), function () use ($csv) {
	$csv->upload($_FILES['file']);
}, $csrf_key, Utils::getSelfURI());

$form->runIf(f('import') && $csv->loaded(), function () use (&$csv) {
	$csv->skip((int)f('skip_first_line'));
	$csv->setTranslationTable(f('translation_table'));

	try {
		if (!$csv->ready()) {
			$csv->clear();
			throw new UserException('Erreur dans le chargement du CSV');
		}

		Subscriptions::import($csv);
	}
	finally {
		$csv->clear();
	}
}, $csrf_key, '!services/import.php?msg=OK');

$tpl->assign(compact('csv', 'csrf_key'));

$tpl->display('services/import.tpl');
