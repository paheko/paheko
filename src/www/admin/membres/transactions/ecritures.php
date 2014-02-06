<?php
namespace Garradin;

require_once __DIR__ . '/../../_inc.php';

if ($user['droits']['membres'] < Membres::DROIT_ECRITURE)
{
    throw new UserException("Vous n'avez pas le droit d'accéder à cette page.");
}

if (empty($_GET['id']) || !is_numeric($_GET['id']))
{
    throw new UserException("Argument du numéro de paiement manquant.");
}

$id = (int) $_GET['id'];

$transactions = new Transactions;
$m_transactions = new Membres_Transactions;

$tr = $m_transactions->get($id);

if (!$tr)
{
    throw new UserException("Ce paiement n'existe pas.");
}

$membre = $membres->get($tr['id_membre']);

if (!$membre)
{
    throw new UserException("Le membre lié au paiement n'existe pas ou plus.");
}


$comptes = new Compta_Comptes;
$liste_comptes = $comptes->getListAll();

function get_nom_compte($compte)
{
	if (is_null($compte))
		return '';

	global $liste_comptes;
	return $liste_comptes[$compte];
}

$tpl->register_modifier('get_nom_compte', 'Garradin\get_nom_compte');

$tpl->assign('transaction', $tr);
$tpl->assign('membre', $membre);
$tpl->assign('operations', $m_transactions->listOperationsCompta($tr['id']));

$tpl->display('admin/membres/transactions/ecritures.tpl');

?>