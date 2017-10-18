<?php
namespace Garradin;

require_once __DIR__ . '/_inc.php';

$session->requireAccess('membres', Membres::DROIT_ECRITURE);

qv(['id' => 'required|numeric']);

$id = (int) qg('id');

$membre = $membres->get($id);

if (!$membre)
{
    throw new UserException("Ce membre n'existe pas.");
}

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
$tpl->assign('cotisations', $cotisations->listForMember($membre->id));
$tpl->assign('cotisations_membre', $cotisations->listSubscriptionsForMember($membre->id));

$tpl->assign('membre', $membre);

$tpl->display('admin/membres/cotisations.tpl');
