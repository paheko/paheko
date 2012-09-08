<?php

require_once __DIR__ . '/_inc.php';

if ($user['droits']['compta'] < Garradin_Membres::DROIT_ECRITURE)
{
    throw new UserException("Vous n'avez pas le droit d'accéder à cette page.");
}

require_once GARRADIN_ROOT . '/include/class.compta_journal.php';
$journal = new Garradin_Compta_Journal;

$operation = $journal->get(utils::get('id'));

if (!$operation)
{
    throw new UserException("L'opération demandée n'existe pas.");
}

$tpl->assign('operation', $operation);

$credit = $comptes->get($operation['compte_credit']);
$tpl->assign('nom_compte_credit', $credit['libelle']);

$debit = $comptes->get($operation['compte_debit']);
$tpl->assign('nom_compte_debit', $debit['libelle']);

if ($operation['id_categorie'])
{
    require_once GARRADIN_ROOT . '/include/class.compta_categories.php';
    $cats = new Garradin_Compta_Categories;

    $categorie = $cats->get($operation['id_categorie']);
    $tpl->assign('categorie', $categorie);

    if ($categorie['type'] == Garradin_Compta_Categories::RECETTES)
    {
        $tpl->assign('compte', $debit['libelle']);
    }
    else
    {
        $tpl->assign('compte', $credit['libelle']);
    }

    $tpl->assign('moyen_paiement', $cats->getMoyenPaiement($operation['moyen_paiement']));
}

$auteur = $membres->get($operation['id_auteur']);
$tpl->assign('nom_auteur', $auteur['nom']);

$tpl->display('admin/compta/operation.tpl');

?>