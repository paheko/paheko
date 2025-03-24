<?php
namespace Paheko;

use Paheko\Entities\Accounting\Account;
use Paheko\Entities\Accounting\Transaction;
use Paheko\Accounting\Reports;
use Paheko\Accounting\Years;
use Paheko\Users\Session;

require_once __DIR__ . '/../_inc.php';

$session = Session::getInstance();
$session->requireAccess($session::SECTION_ACCOUNTING, $session::ACCESS_ADMIN);

$year = Years::get((int)qg('id'));

if (!$year) {
	throw new UserException('Exercice inconnu.');
}

$year->assertCanBeModified();

$csrf_key = 'acc_pro_' . $year->id();
$edit = $_GET['edit'] ?? null;

$form->runIf('save', function () use ($year) {
	$lines = Utils::array_transpose($_POST['lines'] ?? []);

	foreach ($lines as &$row) {
		if (isset($row['account']) && is_array($row['account'])) {
			$row['id_account'] = key($row['account']);
		}

		$row['amount'] = Utils::moneyToInteger($row['amount']);
	}

	unset($row);

	$year->saveProvisional($lines);
}, $csrf_key, '!acc/years/provisional.php?id=' . $year->id());

$pro = $year->getProvisional();

// Force edit
if (!$edit && empty($pro['expense']) && empty($pro['revenue'])) {
	$edit = true;
}

if ($edit) {
	if (empty($pro['expense'])) {
		$pro['expense'] = [[]];
	}

	if (empty($pro['revenue'])) {
		$pro['revenue'] = [[]];
	}
}

$tpl->assign([
	'type_expense' => Account::TYPE_EXPENSE,
	'type_revenue' => Account::TYPE_REVENUE,
]);

$tpl->assign(compact('csrf_key', 'pro', 'year', 'edit'));

$tpl->display('acc/years/provisional.tpl');
