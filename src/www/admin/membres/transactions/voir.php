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

$tr = $transactions->get($id);

if (!$tr)
{
    throw new UserException("Ce paiement n'existe pas.");
}

$page = (int) utils::get('p') ?: 1;

$tpl->assign('page', $page);
$tpl->assign('bypage', Membres_Transactions::ITEMS_PER_PAGE);
$tpl->assign('total', $m_transactions->countForTransaction($tr['id']));
$tpl->assign('pagination_url', utils::getSelfUrl(true) . '?id=' . $tr['id'] . '&amp;p=[ID]');

$tpl->assign('cotisation', $tr);
$tpl->assign('liste', $m_transactions->listForTransaction($tr['id'], $page));

$tpl->display('admin/membres/transactions/voir.tpl');

?>