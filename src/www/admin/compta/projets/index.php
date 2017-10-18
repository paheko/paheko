<?php

namespace Garradin;

require_once __DIR__ . '/../_inc.php';

$projets = new Compta\Projets;

$action = null;
$id = null;

if (qg('supprimer'))
{
	$session->requireAccess('compta', Membres::DROIT_ADMIN);

	$action = 'supprimer';
	$id = (int) qg('supprimer');
}
elseif (qg('modifier'))
{
	$session->requireAccess('compta', Membres::DROIT_ADMIN);

	$action = 'modifier';
	$id = (int) qg('modifier');
}

if ($id)
{
	if (!($projet = $projets->get($id)))
	{
		throw new UserException('Ce projet n\'existe pas.');
	}
	
	$tpl->assign('projet', $projet);
}


if (f('ajouter') && $form->check('ajout_projet'))
{
	$session->requireAccess('compta', Membres::DROIT_ADMIN);

	try {
		$projets->add(f('libelle'));
		Utils::redirect('/admin/compta/projets/');
	}
	catch (UserException $e)
	{
		$form->addError($e->getMessage());
	}
}
elseif (f('modifier') && $form->check('modifier_projet_' . $id))
{
	try {
		$projets->edit($id, f('libelle'));
		Utils::redirect('/admin/compta/projets/');
	}
	catch (UserException $e)
	{
		$form->addError($e->getMessage());
	}
}
elseif (f('supprimer') && $form->check('supprimer_projet_' . $id))
{
	try {
		$projets->remove($id);
		Utils::redirect('/admin/compta/projets/');
	}
	catch (UserException $e)
	{
		$form->addError($e->getMessage());
	}
}


$tpl->assign('action', $action);
$tpl->assign('liste', $projets->getList());

$tpl->display('admin/compta/projets/index.tpl');
