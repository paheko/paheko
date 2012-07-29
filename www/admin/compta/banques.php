<?php

require_once __DIR__ . '/_inc.php';

if ($user['droits']['compta'] < Garradin_Membres::DROIT_ECRITURE)
{
    throw new UserException("Vous n'avez pas le droit d'accéder à cette page.");
}

require_once GARRADIN_ROOT . '/include/class.compta_comptes_bancaires.php';
$banques = new Garradin_Compta_Comptes_Bancaires;

require_once GARRADIN_ROOT . '/include/class.compta_journal.php';
$journal = new Garradin_Compta_Journal;

$liste = $banques->getList();

foreach ($liste as $banque)
{
    $banque['solde'] = $journal->getSolde($banque['id']);
}

$tpl->assign('liste', $liste);

$tpl->display('admin/compta/banques.tpl');

?>