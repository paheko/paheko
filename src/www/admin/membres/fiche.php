<?php
namespace Garradin;

require_once __DIR__ . '/_inc.php';

qv(['id' => 'required|numeric']);

$id = (int) qg('id');

$membre = $membres->get($id);

if (!$membre)
{
    throw new UserException("Ce membre n'existe pas.");
}

$champs = $config->get('champs_membres');
$tpl->assign('champs', $champs->getList());

$cats = new Membres\Categories;

$categorie = $cats->get($membre->id_categorie);
$tpl->assign('categorie', $categorie);

$cotisations = new Membres\Cotisations;

if (!empty($categorie->id_cotisation_obligatoire))
{
	$tpl->assign('cotisation', $cotisations->isMemberUpToDate($membre->id, $categorie->id_cotisation_obligatoire));
}
else
{
	$tpl->assign('cotisation', false);
}

$tpl->assign('nb_activites', $cotisations->countForMember($membre->id));

if ($session->canAccess('compta', Membres::DROIT_ACCES))
{
	$journal = new Compta\Journal;
	$tpl->assign('nb_operations', $journal->countForMember($membre->id));
}

$tpl->assign('membre', $membre);

$tpl->display('admin/membres/fiche.tpl');
