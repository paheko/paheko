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

$champs = $config->get('champs_membres');
$tpl->assign('champs', $champs->getAll());

$cats = new Membres_Categories;

$categorie = $cats->get($membre['id_categorie']);
$tpl->assign('categorie', $categorie);

$m_transactions = new Membres_Transactions;

if (!empty($categorie['id_transaction_obligatoire']))
{
	$tpl->assign('cotisation', $m_transactions->isMemberUpToDate($membre['id'], $categorie['id_transaction_obligatoire']));
}
else
{
	$tpl->assign('cotisation', false);
}

$tpl->assign('nb_paiements', $m_transactions->countForMember($membre['id']));

$tpl->assign('membre', $membre);

$tpl->display('admin/membres/fiche.tpl');

?>