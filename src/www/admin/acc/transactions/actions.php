<?php

namespace Garradin;

use Garradin\Accounting\Transactions;

require_once __DIR__ . '/../_inc.php';

$session->requireAccess('compta', Membres::DROIT_ADMIN);

$check = f('check');

if (!$check || !is_array($check)) {
	throw new UserException('Aucune écriture n\'a été sélectionnée.');
}

$csrf_key = 'acc_actions';

$form->runIf('delete', function () use ($check) {
	foreach ($check as $id) {
		$transaction = Transactions::get((int) $id);

		if (!$transaction) {
			throw new UserException('Cette écriture n\'existe pas');
		}

		$transaction->delete();
	}
}, $csrf_key, f('from') ?: ADMIN_URL);

$from = f('from');
$extra = compact('check', 'from');

$count = count($check);
$tpl->assign(compact('csrf_key', 'check', 'count', 'extra'));

$tpl->display('acc/transactions/actions_delete.tpl');
