<?php
namespace Garradin;

require_once __DIR__ . '/../_inc.php';

$exercices = new Compta\Exercices;

$exercice = $exercices->get((int)Utils::get('id'));

if (!$exercice)
{
	throw new UserException('Exercice inconnu.');
}

$liste_comptes = $comptes->getListAll();

function get_nom_compte($compte)
{
	if (is_null($compte))
		return '';

	global $liste_comptes;
	return $liste_comptes[$compte];
}

$tpl->register_modifier('get_nom_compte', 'Garradin\get_nom_compte');
$tpl->assign('journal', $exercices->getJournal($exercice['id']));

$tpl->assign('cloture', $exercice['cloture'] ? $exercice['fin'] : time());
$tpl->assign('exercice', $exercice);

$tpl->display('admin/compta/exercices/journal.tpl');

?>