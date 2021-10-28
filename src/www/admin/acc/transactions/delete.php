<?php

namespace Garradin;

use Garradin\Accounting\Transactions;

require_once __DIR__ . '/../_inc.php';

$session->requireAccess($session::SECTION_ACCOUNTING, $session::ACCESS_ADMIN);

$transaction = Transactions::get((int) qg('id'));

if (!$transaction) {
	throw new UserException('Cette écriture n\'existe pas');
}

if ($transaction->validated) {
	throw new UserException('Cette écriture est validée et ne peut être modifiée');
}

if (f('delete') && $form->check('acc_delete_' . $transaction->id))
{
	try
	{
		$transaction->delete();
		Utils::redirect(ADMIN_URL . 'acc/');
	}
	catch (UserException $e)
	{
		$form->addError($e->getMessage());
	}
}

$tpl->assign('transaction', $transaction);

$tpl->display('acc/transactions/delete.tpl');
