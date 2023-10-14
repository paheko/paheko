<?php

namespace Paheko;

use Paheko\Accounting\Projects;
use Paheko\Accounting\Transactions;
use Paheko\Accounting\Years;

require_once __DIR__ . '/../_inc.php';

$session->requireAccess($session::SECTION_ACCOUNTING, $session::ACCESS_ADMIN);

$check = f('check');

if (!$check || !is_array($check)) {
	throw new UserException('Aucune écriture n\'a été sélectionnée.');
}

$transactions = array_unique(array_values($check));
$lines = array_keys($check);

$csrf_key = 'acc_actions';

// Delete transactions
$form->runIf('delete', function () use ($transactions) {
	foreach ($transactions as $id) {
		$transaction = Transactions::get((int) $id);

		if (!$transaction) {
			throw new UserException('Cette écriture n\'existe pas');
		}

		$transaction->delete();
	}
}, $csrf_key, f('from') ?: ADMIN_URL);

// Add/remove lines to analytical project
$form->runIf('change_project', function () use ($transactions, $lines) {
	$id = f('id_project') ?: null;

	if (f('apply_lines')) {
		$lines = null;
	}
	else {
		$transactions = null;
	}

	Transactions::setProject($id, $transactions, $lines);
}, $csrf_key, f('from') ?: ADMIN_URL);

$from = f('from');
$count = count($check);
$extra = compact('check', 'from');
$tpl->assign(compact('csrf_key', 'check', 'count', 'extra'));

if (f('action') == 'delete')
{
	$tpl->display('acc/transactions/actions_delete.tpl');
}
else
{
	$tpl->assign('projects', Projects::listAssoc());

	$tpl->display('acc/transactions/action_project.tpl');
}
