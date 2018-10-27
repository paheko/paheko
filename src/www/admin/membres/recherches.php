<?php
namespace Garradin;

require_once __DIR__ . '/_inc.php';

$recherche = new Recherche;
$mode = null;

if (qg('edit') || qg('delete'))
{
	$r = $recherche->get(qg('edit') ?: qg('delete'));

	if (!$r)
	{
		throw new UserException('Recherche non trouvée');
	}

	if ($r->id_membre !== null && $r->id_membre != $user->id)
	{
		throw new UserException('Recherche privée appartenant à un autre membre.');
	}

	$tpl->assign('recherche', $r);

	$mode = qg('edit') ? 'edit' : 'delete';
}

if ($mode == 'edit' && f('save') && $form->check('edit_recherche_' . $r->id))
{
	try {
		$recherche->edit($r->id, [
			'intitule'  => f('intitule'),
			'id_membre' => f('prive') ? $user->id : null,
		]);

		Utils::redirect('/admin/membres/recherches.php');
	}
	catch (UserException $e) {
		$form->addError($e->getMessage());
	}
}
elseif ($mode == 'delete' && f('delete') && $form->check('del_recherche_' . $r->id))
{
	$recherche->remove($r->id);
	Utils::redirect('/admin/membres/recherches.php');
}

$tpl->assign('mode', $mode);

if (!$mode)
{
	$tpl->assign('liste', $recherche->getList($user->id, 'membres'));
}

$tpl->display('admin/membres/recherches.tpl');
