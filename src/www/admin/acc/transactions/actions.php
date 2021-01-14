<?php

namespace Garradin;

use Garradin\Accounting\Transactions;
use Garradin\Accounting\Years;

require_once __DIR__ . '/../_inc.php';

$session->requireAccess('compta', Membres::DROIT_ADMIN);

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

// Add/remove lines to analytical
$form->runIf('change_analytical', function () use ($lines) {
	$id = f('id_analytical') ?: null;
	Transactions::setAnalytical($id, $lines);
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
	// Get year to get analytical accounts
	$year = Years::get((int) f('year'));

	if (!$year) {
		throw new UserException("Aucun exercice sélectionné.");
	}

	$analytical = $year->chart()->accounts()->listAnalytical();
	$tpl->assign('analytical_accounts', ['' => '-- Aucun projet'] + $analytical);

	$tpl->display('acc/transactions/actions_analytical.tpl');
}
