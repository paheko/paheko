<?php
namespace Paheko;

use Paheko\DB;
use Paheko\Users\Session;
use Paheko\Accounting\Charts;
use Paheko\Accounting\Transactions;
use Paheko\Accounting\Years;
use Paheko\Entities\Accounting\Account;

require_once __DIR__ . '/../../_inc.php';

$session->requireAccess($session::SECTION_ACCOUNTING, $session::ACCESS_ADMIN);

$csrf_key = 'first_setup';

$year = Years::create();

$config = Config::getInstance();
$default_chart_code = Charts::getFirstForCountry($config->country);
$default_chart_label = Charts::BUNDLED_CHARTS[$default_chart_code];
$selected_chart = f('chart');

if ($id_chart = (int) f('id_chart')) {
	$year->id_chart = $id_chart;
}
elseif ($selected_chart) {
	$year->id_chart = Charts::getOrInstall($selected_chart);
}

$new_accounts = f('accounts');

if (is_array($new_accounts)) {
	$new_accounts = Utils::array_transpose($new_accounts);

	foreach ($new_accounts as &$line) {
		if (isset($line['balance'])) {
			$line['balance'] = Utils::moneyToInteger($line['balance']);
		}
	}

	unset($line);
}
else {
	$new_accounts = [];
}

$appropriation_account = $year->id_chart ? $year->chart()->accounts()->getSingleAccountForType(Account::TYPE_APPROPRIATION_RESULT) : null;

$form->runIf('save', function () use ($year, $new_accounts, $appropriation_account) {
	$db = DB::getInstance();

	$db->begin();
	$year->importForm();
	$year->label = sprintf('Exercice %s', $year->label_years());
	$year->save();

	foreach ($new_accounts as $row) {
		if (empty($row['label'])) {
			continue;
		}

		$account = new Account;
		$account->bookmark = true;
		$account->user = true;
		$account->id_chart = $year->id_chart;
		$account->type = $account::TYPE_BANK;
		$account->code = $account->getNumberBase() . $account->getNewNumberAvailable();
		$account->import(['label' => $row['label'] ?? '']);
		$account->save();

		if (trim($row['balance'] ?? '')) {
			$t = $account->createOpeningBalance($year, $row['balance']);
			$t->id_creator = Session::getUserId();
			$t->save();
		}
	}

	if (f('result') && $appropriation_account) {
		$t = $appropriation_account->createOpeningBalance($year, Utils::moneyToInteger(f('result')) * -1, 'Report du résultat de l\'exercice précédent');
		$t->id_creator = Session::getUserId();
		$t->save();
	}

	$db->commit();
}, $csrf_key, '!acc/years/?msg=WELCOME');

if (!count($new_accounts)) {
	$new_accounts[] = ['label' => 'Compte courant', 'balance' => 0];
}

$step = (int)f('step');
$charts_list = Charts::listForCountry($config->country);
$tpl->assign(compact('year', 'new_accounts', 'csrf_key', 'appropriation_account', 'charts_list', 'default_chart_label', 'default_chart_code', 'step'));

$tpl->display('acc/years/first_setup.tpl');
