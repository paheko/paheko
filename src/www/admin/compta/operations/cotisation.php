<?php
namespace Garradin;

require_once __DIR__ . '/../_inc.php';

$journal = new Compta\Journal;

qv(['id' => 'required|numeric']);

$id = (int) qg('id');

$m_cotisations = new Membres\Cotisations;
$cotisations = new Cotisations;

$mco = $m_cotisations->get($id);

if (!$mco)
{
	throw new UserException("La cotisation demandée n'existe pas.");
}

$co = $cotisations->get($mco->id_cotisation);
$membre = (new Membres)->get($mco->id_membre);

if (!$membre)
{
    throw new UserException("Le membre demandé n'existe pas.");
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

$tpl->assign('journal', $m_cotisations->listOperationsCompta($mco->id));

$tpl->assign('cotisation_membre', $mco);
$tpl->assign('cotisation', $co);
$tpl->assign('membre', $membre);

$tpl->display('admin/compta/operations/cotisation.tpl');
