<?php

require_once __DIR__ . '/_inc.php';

if ($user['droits']['compta'] < Garradin_Membres::DROIT_ECRITURE)
{
    throw new UserException("Vous n'avez pas le droit d'accéder à cette page.");
}

require_once GARRADIN_ROOT . '/include/class.compta_journal.php';
$journal = new Garradin_Compta_Journal;

$tpl->assign('solde', $journal->getSolde(Garradin_Compta_Comptes::CAISSE));
$tpl->assign('journal', $journal->getJournalCompte(Garradin_Compta_Comptes::CAISSE));

$tpl->display('admin/compta/caisse.tpl');

?>