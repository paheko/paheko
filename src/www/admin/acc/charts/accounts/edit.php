<?php
namespace Garradin;

use Garradin\Accounting\Accounts;

require_once __DIR__ . '/../../_inc.php';

$session->requireAccess($session::SECTION_ACCOUNTING, $session::ACCESS_ADMIN);

$account = Accounts::get((int) qg('id'));

if (!$account) {
	throw new UserException("Le compte demandé n'existe pas.");
}

$chart = $account->chart();

if ($chart->archived) {
	throw new UserException("Il n'est pas possible de modifier un compte d'un plan comptable archivé.");
}

$edit_disabled = !$account->canEdit();

if (f('edit') && $form->check('acc_accounts_edit_' . $account->id()))
{
	try {
		if ($edit_disabled) {
			$account->importLimitedForm();
		}
		else {
			$account->importForm();
		}

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

$types = $account::TYPES_NAMES;
$types[0] = '-- Pas un compte favori';

$tpl->assign(compact('types', 'account', 'edit_disabled'));

$tpl->display('acc/charts/accounts/edit.tpl');
