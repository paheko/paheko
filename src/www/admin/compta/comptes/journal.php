<?php
namespace Garradin;

require_once __DIR__ . '/../_inc.php';

$compte = $comptes->get(qg('id'));

if (!$compte)
{
    throw new UserException("Le compte demandé n'existe pas.");
}

$journal = new Compta\Journal;

// Récupération de l'exercice courant et sélectionné
$exercices = new Compta\Exercices;
$exercice = (int) qg('exercice') ?: $exercices->getCurrent()->id;

$solde = $journal->getSolde($compte->id, false, $exercice);

if (($compte->position & Compta\Comptes::ACTIF) || ($compte->position & Compta\Comptes::CHARGE))
{
    $tpl->assign('credit', '-');
    $tpl->assign('debit', '+');
}
else
{
    $tpl->assign('credit', '+');
    $tpl->assign('debit', '-');
}

$tpl->assign('exercices', $exercices->getList());
$tpl->assign('exercice_selectionne', $exercice);

$tpl->assign('compte', $compte);
$tpl->assign('solde', $solde);
$tpl->assign('journal', $journal->getJournalCompte($compte->id, false, $exercice));
$tpl->assign('suivi', qg('suivi'));

$tpl->display('admin/compta/comptes/journal.tpl');
