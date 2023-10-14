<?php
namespace Paheko;

use Paheko\DB;
use Paheko\Users\Session;
use Paheko\Accounting\Charts;
use Paheko\Accounting\Transactions;
use Paheko\Accounting\Years;
use Paheko\Entities\Accounting\Account;
use Paheko\Entities\Accounting\Year;

require_once __DIR__ . '/../../_inc.php';

$session->requireAccess($session::SECTION_ACCOUNTING, $session::ACCESS_ADMIN);

$csrf_key = 'first_setup';

$year = new Year;

$config = Config::getInstance();
$default_chart = Charts::getFirstForCountry($config->country);
$selected_chart = f('chart');

if ($id_chart = (int) f('id_chart')) {
	$year->id_chart = $id_chart;
}
elseif ($selected_chart) {
	$year->id_chart = Charts::getOrInstall($selected_chart);
}
elseif ($default_chart) {
	$year->id_chart = $default_chart->id;
}

$new_dates = Years::getNewYearDates();
$year->start_date = $new_dates[0];
$year->end_date = $new_dates[1];
$year->label = sprintf('Exercice %s', $year->label_years());

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
$default_chart_code = $default_chart ? $default_chart->country_code() : null;
$tpl->assign(compact('year', 'new_accounts', 'csrf_key', 'appropriation_account', 'charts_list', 'default_chart', 'default_chart_code', 'step'));

$tpl->display('acc/years/first_setup.tpl');
