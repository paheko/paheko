<?php
namespace Garradin;

require_once __DIR__ . '/../../_inc.php';

if ($user['droits']['membres'] < Membres::DROIT_ECRITURE)
{
    throw new UserException("Vous n'avez pas le droit d'accéder à cette page.");
}

$transactions = new Transactions;

$tpl->assign('liste', $transactions->listCurrentWithStats());

$tpl->display('admin/membres/transactions/index.tpl');

?>