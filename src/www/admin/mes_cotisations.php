<?php
namespace Garradin;

require_once __DIR__ . '/_inc.php';

$tpl->assign('membre', $user);

$cats = new Membres\Categories;

$categorie = $cats->get($user->id_categorie);
$tpl->assign('categorie', $categorie);

$cotisations = new Membres\Cotisations;

if (!empty($categorie->id_cotisation_obligatoire))
{
    $tpl->assign('cotisation', $cotisations->isMemberUpToDate($user->id, $categorie->id_cotisation_obligatoire));
}
else
{
    $tpl->assign('cotisation', false);
}

$tpl->assign('nb_activites', $cotisations->countForMember($user->id));
$tpl->assign('cotisations', $cotisations->listForMember($user->id));
$tpl->assign('cotisations_membre', $cotisations->listSubscriptionsForMember($user->id));

$tpl->display('admin/mes_cotisations.tpl');
