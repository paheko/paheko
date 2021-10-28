<?php
namespace Garradin;

require_once __DIR__ . '/../_inc.php';

if (empty($target) || !in_array($target, Recherche::TARGETS)) {
    throw new UserException('Cible inconnue');
}

if (empty($search_url)) {
	throw new \LogicException('Missing $search_url');
}

$access_section = $target == 'compta' ? $session::SECTION_ACCOUNTING : $session::SECTION_USERS;

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

		Utils::redirect(Utils::getSelfURI(false));
	}
	catch (UserException $e) {
		$form->addError($e->getMessage());
	}
}
elseif ($mode == 'delete' && f('delete') && $form->check('del_recherche_' . $r->id))
{
	$recherche->remove($r->id);
	Utils::redirect(Utils::getSelfURI(false));
}

if (!$mode)
{
	$tpl->assign('liste', $recherche->getList($user->id, $target));
}

$tpl->assign(compact('mode', 'target', 'search_url', 'access_section'));

$tpl->display('common/search/saved_searches.tpl');
