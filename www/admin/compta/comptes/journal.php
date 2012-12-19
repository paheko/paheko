<?php
namespace Garradin;

require_once __DIR__ . '/../_inc.php';

$compte = $comptes->get(utils::get('id'));

if (!$compte)
{
    throw new UserException("Le compte demandé n'existe pas.");
}

$journal = new Compta_Journal;

$solde = $journal->getSolde($compte['id']);

if (($compte['position'] & Compta_Comptes::ACTIF) || ($compte['position'] & Compta_Comptes::CHARGE))
{
    $tpl->assign('credit', '-');
    $tpl->assign('debit', '+');
}
else
{
    $tpl->assign('credit', '+');
    $tpl->assign('debit', '-');
}

$tpl->assign('compte', $compte);
$tpl->assign('solde', $solde);
$tpl->assign('journal', $journal->getJournalCompte($compte['id']));

$tpl->display('admin/compta/comptes/journal.tpl');

?>