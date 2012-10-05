<?php

require_once __DIR__ . '/../_inc.php';

require_once GARRADIN_ROOT . '/include/class.compta_journal.php';
$journal = new Garradin_Compta_Journal;

$liste_comptes = $comptes->getListAll();

function get_nom_compte($compte)
{
	global $liste_comptes;
	return $liste_comptes[$compte];
}

$tpl->register_modifier('get_nom_compte', 'get_nom_compte');
$tpl->assign('compte_resultat', $journal->getCompteResultat());

$tpl->assign('now', time());
$tpl->assign('exercice', $journal->getCurrentExercice());

$tpl->display('admin/compta/rapport/compte_resultat.tpl');

?>