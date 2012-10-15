<?php

require_once __DIR__ . '/../_inc.php';

require_once GARRADIN_ROOT . '/include/class.compta_exercices.php';
$exercices = new Garradin_Compta_Exercices;

$exercice = $exercices->get((int)utils::get('id'));

if (!$exercice)
{
	throw new UserException('Exercice inconnu.');
}

$liste_comptes = $comptes->getListAll();

function get_nom_compte($compte)
{
	global $liste_comptes;
	return $liste_comptes[$compte];
}

$tpl->register_modifier('get_nom_compte', 'get_nom_compte');
$tpl->assign('journal', $exercices->getJournal($exercice['id']));

$tpl->assign('cloture', $exercice['cloture'] ? $exercice['fin'] : time());
$tpl->assign('exercice', $exercice);

$tpl->display('admin/compta/exercices/journal.tpl');

?>