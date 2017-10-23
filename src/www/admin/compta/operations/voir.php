<?php
namespace Garradin;

require_once __DIR__ . '/../_inc.php';

$journal = new Compta\Journal;

$operation = $journal->get(qg('id'));

if (!$operation)
{
    throw new UserException("L'opération demandée n'existe pas.");
}
$exercices = new Compta\Exercices;

$tpl->assign('operation', $operation);

$credit = $comptes->get($operation->compte_credit);
$tpl->assign('nom_compte_credit', $credit ? $credit->libelle : null);

$debit = $comptes->get($operation->compte_debit);
$tpl->assign('nom_compte_debit', $debit ? $debit->libelle : null);

$tpl->assign('exercice', $exercices->get($operation->id_exercice));

if ($operation->id_categorie)
{
    $cats = new Compta\Categories;

    $categorie = $cats->get($operation->id_categorie);
    $tpl->assign('categorie', $categorie);

    if ($categorie->type == Compta\Categories::RECETTES)
    {
        $tpl->assign('compte', $debit->libelle);
    }
    else
    {
        $tpl->assign('compte', $credit->libelle);
    }

    $tpl->assign('moyen_paiement', $cats->getMoyenPaiement($operation->moyen_paiement));
}

if ($operation->id_projet)
{
    $tpl->assign('projet', (new Compta\Projets)->get($operation->id_projet));
}

if ($operation->id_auteur)
{
    $auteur = (new Membres)->get($operation->id_auteur);
    $tpl->assign('nom_auteur', $auteur->identite);
}

$tpl->assign('related_members', $journal->listRelatedMembers($operation->id));

$tpl->display('admin/compta/operations/voir.tpl');
