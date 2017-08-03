<?php
namespace Garradin;

require_once __DIR__ . '/../_inc.php';

$exercices = new Compta\Exercices;
$journal = new Compta\Journal;

$exercice = qg('exercice') ?: $exercices->getCurrentId();

if (!$exercice)
{
	throw new UserException('Exercice inconnu.');
}

qv(['id' => 'required|numeric']);

$id = (int) qg('id');

$membre = (new Membres)->get($id);

if (!$membre)
{
    throw new UserException("Le membre demandé n'existe pas.");
}

$liste_comptes = $comptes->getListAll();

function get_nom_compte($compte)
{
	if (is_null($compte))
		return '';

	global $liste_comptes;
	return $liste_comptes[$compte];
}

$tpl->register_modifier('get_nom_compte', 'Garradin\get_nom_compte');

$tpl->assign('journal', $journal->listForMember($membre->id, $exercice));

$tpl->assign('exercices', $exercices->getList());
$tpl->assign('exercice', $exercice);
$tpl->assign('membre', $membre);

$tpl->display('admin/compta/operations/membre.tpl');
