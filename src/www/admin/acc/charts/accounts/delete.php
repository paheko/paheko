<?php
namespace Garradin;

use Garradin\Accounting\Accounts;

require_once __DIR__ . '/../../_inc.php';

$session->requireAccess('compta', Membres::DROIT_ADMIN);

$account = Accounts::get((int) qg('id'));

if (!$account) {
	throw new UserException("Le compte demandé n'existe pas.");
}

if (!$account->canDelete()) {
	throw new UserException("Ce compte ne peut être supprimé car des écritures y sont liées (peut-être sur l'exercice courant ou sur un exercice clôt).\nSi vous souhaitez faire du ménage dans la liste des comptes il est recommandé de créer un nouveau comptable.");
}

if (f('delete') && $form->check('acc_accounts_delete_' . $account->id()))
{
	try
	{
		$page = '';

		if (!$account->type) {
			$page = 'all.php';
		}

		$account->delete();

		Utils::redirect(sprintf('%sacc/charts/accounts/%s?id=%d', ADMIN_URL, $page, $account->id_chart));
	}
	catch (UserException $e)
	{
		$form->addError($e->getMessage());
	}
}

$tpl->assign(compact('account'));

$tpl->display('acc/charts/accounts/delete.tpl');
