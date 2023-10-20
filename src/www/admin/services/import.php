<?php

namespace Paheko;

use Paheko\CSV_Custom;
use Paheko\Users\Session;
use Paheko\Users\Users;
use Paheko\Services\Services_User;

require_once __DIR__ . '/_inc.php';

$session = Session::getInstance();
$session->requireAccess($session::SECTION_USERS, $session::ACCESS_ADMIN);

$csrf_key = 'su_import';
$csv = new CSV_Custom($session, 'su_import');

$csv->setColumns(Services_User::listImportColumns());
$csv->setMandatoryColumns(Services_User::listMandatoryImportColumns());

$form->runIf('cancel', function() use ($csv) {
	$csv->clear();
}, $csrf_key, Utils::getSelfURI());

$form->runIf(f('load') && isset($_FILES['file']['tmp_name']), function () use ($csv) {
	$csv->load($_FILES['file']);
}, $csrf_key, Utils::getSelfURI());

$form->runIf(f('import') && $csv->loaded(), function () use (&$csv) {
	$csv->skip((int)f('skip_first_line'));
	$csv->setTranslationTable(f('translation_table'));

	try {
		if (!$csv->ready()) {
			$csv->clear();
			throw new UserException('Erreur dans le chargement du CSV');
		}

		Services_User::import($csv);
	}
	finally {
		$csv->clear();
	}
}, $csrf_key, '!users/import.php?msg=OK');

$tpl->assign(compact('csv', 'csrf_key'));

$tpl->display('services/import.tpl');
