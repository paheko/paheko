<?php
namespace Garradin;

require_once __DIR__ . '/../_inc.php';

if ($user['droits']['membres'] < Membres::DROIT_ECRITURE)
{
    throw new UserException("Vous n'avez pas le droit d'accéder à cette page.");
}

if (empty($_GET['id']) || !is_numeric($_GET['id']))
{
    throw new UserException("Argument du numéro de membre manquant.");
}

$id = (int) $_GET['id'];

$membre = $membres->get($id);

if (!$membre)
{
    throw new UserException("Ce membre n'existe pas.");
}

$cats = new Membres_Categories;

$categorie = $cats->get($membre['id_categorie']);
$tpl->assign('categorie', $categorie);

$m_transactions = new Membres_Transactions;

if (!empty($categorie['id_transaction_obligatoire']))
{
	$transactions = new Transactions;
	$tr = $transactions->get($categorie['id_transaction_obligatoire']);

	$tpl->assign('cotisation', $tr);
	$tpl->assign('statut_cotisation', $m_transactions->isMemberUpToDate($membre['id'], $tr));
}
else
{
	$tpl->assign('cotisation', false);
}

$tpl->assign('nb_paiements', $m_transactions->countForMember($membre['id']));
$tpl->assign('paiements', $m_transactions->listForMember($membre['id']));
$tpl->assign('activites', $m_transactions->listCurrentSubscriptionsForMember($membre['id']));

$tpl->assign('membre', $membre);

$tpl->display('admin/membres/transactions.tpl');

?>