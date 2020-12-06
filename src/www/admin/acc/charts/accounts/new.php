<?php
namespace Garradin;

use Garradin\Entities\Accounting\Account;
use Garradin\Accounting\Accounts;
use Garradin\Accounting\Charts;

require_once __DIR__ . '/../../_inc.php';

$session->requireAccess('compta', Membres::DROIT_ADMIN);

$chart = Charts::get((int)qg('id'));

if (!$chart) {
	throw new UserException('Ce plan comptable n\'existe pas');
}

if ($chart->archived) {
	throw new UserException("Il n'est pas possible de modifier un plan comptable archivé.");
}

$account = new Account;
$account->position = Account::ASSET_OR_LIABILITY;

$types = $account::TYPES_NAMES;
$types[0] = '-- Pas un compte favori';

$translate_type_position = [
	Account::TYPE_REVENUE => Account::REVENUE,
	Account::TYPE_EXPENSE => Account::EXPENSE,
];

$translate_type_codes = $chart->accounts()->getNextCodesForTypes();

$simple = false;

// Simple creation with pre-determined account type
if ($type = (int)qg('type')) {
	$account->type = $type;

	$simple = true;

	$types = array_slice($types, 1, null, true);

	if (isset($translate_type_codes[$type])) {
		$account->code = $translate_type_codes[$type];
	}
}


if (f('save') && $form->check('acc_accounts_new'))
{
	try
	{
		if ($simple) {
			$account->importSimpleForm($translate_type_position, $translate_type_codes);
		}
		else {
			$account->importForm();
		}

		$account->id_chart = $chart->id();
		$account->user = 1;
		$account->save();

		$page = '';

		if (!$account->type) {
			$page = 'all.php';
		}

		Utils::redirect(sprintf('%sacc/charts/accounts/%s?id=%d', ADMIN_URL, $page, $account->id_chart));
	}
	catch (UserException $e)
	{
		$form->addError($e->getMessage());
	}
}

$tpl->assign(compact('simple', 'types', 'account', 'translate_type_position', 'translate_type_codes', 'chart'));

$tpl->display('acc/charts/accounts/new.tpl');
