<?php
namespace Garradin;

require_once __DIR__ . '/../_inc.php';

$exercices = new Compta\Exercices;
$journal = new Compta\Journal;

$exercice = Utils::get('exercice') ?: $exercices->getCurrentId();

if (!$exercice)
{
	throw new UserException('Exercice inconnu.');
}

if (empty($_GET['id']) || !is_numeric($_GET['id']))
{
    throw new UserException("Argument du numéro de membre manquant.");
}

$id = (int) $_GET['id'];

$membre = $membres->get($id);

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

$tpl->assign('journal', $journal->listForMember($membre['id'], $exercice));

$tpl->assign('exercices', $exercices->getList());
$tpl->assign('exercice', $exercice);
$tpl->assign('membre', $membre);

$tpl->display('admin/compta/operations/membre.tpl');
