<?php

namespace Garradin;

use Garradin\Users\DynamicFields;
use Garradin\Users\Session;
use Garradin\Users\Users;

require_once __DIR__ . '/_inc.php';

Session::getInstance()->requireAccess($session::SECTION_USERS, $session::ACCESS_ADMIN);

if ($format = qg('export')) {
	Users::export($format);
	return;
}

$csrf_key = 'user_import';
$csv = new CSV_Custom($session, 'users_import');
$ignore_ids = (bool) (f('ignore_ids') ?? qg('ignore_ids'));
$report = [];

$df = DynamicFields::getInstance();

$params = compact('ignore_ids');

$csv->setColumns($df->listImportAssocNames());
$csv->setMandatoryColumns(array_keys($df->listImportRequiredAssocNames()));

$form->runIf('cancel', function() use ($csv) {
	$csv->clear();
}, $csrf_key, Utils::getSelfURI());

$form->runIf(f('load') && isset($_FILES['file']['tmp_name']), function () use ($csv, $params) {
	$csv->load($_FILES['file']);
	Utils::redirect(Utils::getSelfURI($params));
}, $csrf_key);

$form->runIf(f('preview') && $csv->loaded(), function () use (&$csv) {
	$csv->skip((int)f('skip_first_line'));
	$csv->setTranslationTable(f('translation_table'));
}, $csrf_key);

if (!f('import') && $csv->ready()) {
	$report = Users::importReport($csv, $ignore_ids, Session::getUserId());

	if (count($report['errors'])) {
		$csv->clear();

		foreach ($report['errors'] as $msg) {
			$form->addError($msg);
		}
	}
}

$form->runIf('import', function () use ($csv, $ignore_ids) {
	try {
		if (!$csv->ready()) {
			$csv->clear();
			throw new UserException('Erreur dans le chargement du CSV');
		}

		Users::import($csv, $ignore_ids, Session::getUserId());
	}
	finally {
		$csv->clear();
	}
}, $csrf_key, '!users/import.php?msg=OK');

$tpl->assign(compact('csv', 'csrf_key', 'report'));

$tpl->display('users/import.tpl');
