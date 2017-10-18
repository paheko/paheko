<?php

namespace Garradin;

require_once __DIR__ . '/../_inc.php';

$session->requireAccess('compta', Membres::DROIT_ADMIN);

$banque = new Compta\Comptes_Bancaires;

$compte = $banque->get(qg('id'));

if (!$compte)
{
	throw new UserException('Le compte demandé n\'existe pas.');
}

if (f('delete') && $form->check('compta_delete_banque_' . $compte->id))
{
	try
	{
		$banque->delete($compte->id);
		Utils::redirect('/admin/compta/banques/');
	}
	catch (UserException $e)
	{
		$form->addError($e->getMessage());
	}
}

$tpl->assign('compte', $compte);

$tpl->display('admin/compta/banques/supprimer.tpl');
