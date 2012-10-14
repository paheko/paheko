<?php

require_once __DIR__ . '/../_inc.php';

$compte = $comptes->get(utils::get('id'));

if (!$compte)
{
    throw new UserException("Le compte demandé n'existe pas.");
}

require_once GARRADIN_ROOT . '/include/class.compta_journal.php';
$journal = new Garradin_Compta_Journal;

$tpl->assign('compte', $compte);
$tpl->assign('solde', $journal->getSolde($compte['id']));
$tpl->assign('journal', $journal->getJournalCompte($compte['id']));

$tpl->display('admin/compta/comptes/journal.tpl');

?>