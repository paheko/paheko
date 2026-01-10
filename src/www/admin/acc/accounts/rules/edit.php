<?php
namespace Paheko;

use Paheko\Accounting\ImportRules;
use Paheko\Users\Session;

require_once __DIR__ . '/../../_inc.php';

$session = Session::getInstance();
$session->requireAccess($session::SECTION_ACCOUNTING, $session::ACCESS_ADMIN);

if (isset($_GET['id'])) {
	$rule = ImportRules::get(intval($_GET['id']));

	if (!$rule) {
		throw new UserException('Impossible de trouver cette règle.');
	}
}
else {
	$rule = ImportRules::create();
}

$csrf_key = 'rule_edit';

$form->runIf('save', function () use ($rule) {
	$rule->importForm();
	$rule->save();
}, $csrf_key, '!acc/accounts/rules/');

$tpl->assign(compact('csrf_key', 'rule'));

$tpl->display('acc/accounts/rules/edit.tpl');
