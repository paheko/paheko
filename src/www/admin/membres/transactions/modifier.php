<?php
namespace Garradin;

require_once __DIR__ . '/../../_inc.php';

if ($user['droits']['membres'] < Membres::DROIT_ECRITURE)
{
    throw new UserException("Vous n'avez pas le droit d'accéder à cette page.");
}

$membre = false;

$transactions = new Transactions;
$m_transactions = new Membres_Transactions;


if (empty($_GET['id']) || !is_numeric($_GET['id']))
{
    throw new UserException("Argument du numéro de transaction manquant.");
}

$id = (int) $_GET['id'];

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

$error = false;

if (!empty($_POST['edit']))
{
    if (!utils::CSRF_check('edit_transaction_' . $tr['id']))
    {
        $error = 'Une erreur est survenue, merci de renvoyer le formulaire.';
    }
    else
    {
        try {
            $m_transactions->edit($tr['id'], [
                'libelle'           =>  utils::post('libelle'),
                'date'              =>  utils::post('date'),
                'id_transaction'    =>  utils::post('id_transaction'),
                'montant'           =>  (float) utils::post('montant'),
            ]);

            utils::redirect('/admin/membres/transactions.php?id=' . $membre['id']);
        }
        catch (UserException $e)
        {
            $error = $e->getMessage();
        }
    }
}

$tpl->assign('error', $error);
$tpl->assign('membre', $membre);
$tpl->assign('transaction', $tr);
$tpl->assign('transactions', $transactions->listCurrent());

$tpl->display('admin/membres/transactions/modifier.tpl');

?>