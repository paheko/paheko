<?php
namespace Paheko;

use Paheko\DB;
use Paheko\Users\Session;
use Paheko\Accounting\Charts;
use Paheko\Accounting\Transactions;
use Paheko\Accounting\Years;
use Paheko\Entities\Accounting\Account;
use Paheko\Entities\Accounting\Chart;

require_once __DIR__ . '/../../_inc.php';

$session->requireAccess($session::SECTION_ACCOUNTING, $session::ACCESS_ADMIN);

$csrf_key = 'first_setup';

$year = Years::create();

$data = !empty($_POST) ? $_POST : $_GET;
$step = $data['step'] = intval($data['step'] ?? 1);

$data['step']--;
$back_url = '!acc/years/first_setup.php?' . http_build_query($data);
$year = Years::create();

if (!empty($data['chart'])) {
	// Assign already existing user-create chart
	if (ctype_digit($data['chart'])) {
		$year->id_chart = (int) $data['chart'];
	}
	// Install and assign bundled chart
	else {
		$year->id_chart = Charts::getOrInstall($data['chart']);
	}
}

$accounts = null;

if (!empty($data['accounts'])
	&& is_array($data['accounts'])
	&& isset($data['accounts']['label'][0])) {
	$accounts = Utils::array_transpose($data['accounts']);
}

$appropriation_account = $year->id_chart ? $year->chart()->accounts()->getSingleAccountForType(Account::TYPE_APPROPRIATION_RESULT) : null;

$form->runIf('save', function () use ($data, $year, $appropriation_account, $accounts, &$step) {
	if (empty($year->id_chart)) {
		$step = 2;
		throw new UserException('Aucun plan comptable n\'a été sélectionné');
	}

	$db = DB::getInstance();

	$db->begin();
	$year->importForm();
	$year->label = sprintf('Exercice %s', $year->label_years());
	$year->save();

	// Create bank accounts
	foreach ($accounts ?? [] as $row) {
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
			$t = $account->createOpeningBalance($year, Utils::moneyToInteger($row['balance']));
			$t->id_creator = Session::getUserId();
			$t->save();
		}
	}

	// Result appropriation
	$result = abs(Utils::moneyToInteger($data['result'] ?? 0));

	if ($result && $appropriation_account) {
		$result = ($data['negative'] ?? null) ? $result : $result * -1;
		$t = $appropriation_account->createOpeningBalance($year, $result, 'Report du résultat de l\'exercice précédent');
		$t->id_creator = Session::getUserId();
		$t->save();
	}

	$db->commit();
}, $csrf_key, '!acc/years/?msg=WELCOME');


if ($step === 2) {
	$tpl->assign('countries', Chart::COUNTRY_LIST);
	$tpl->assign('countries_charts', Charts::COUNTRIES_CHARTS);
	$charts_list = Charts::listForCountry($config->country);
}
elseif ($step === 3) {
	$accounts ??= [['label' => 'Compte courant', 'balance' => 0]];
}

$tpl->assign('method', $step !== 4 ? 'get' : 'post');
$tpl->assign(compact('data', 'csrf_key', 'step', 'year', 'back_url', 'appropriation_account', 'accounts'));

$tpl->display('acc/years/first_setup.tpl');
