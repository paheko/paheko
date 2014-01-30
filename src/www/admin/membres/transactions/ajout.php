<?php
namespace Garradin;

require_once __DIR__ . '/../../_inc.php';

if ($user['droits']['membres'] < Membres::DROIT_ECRITURE)
{
    throw new UserException("Vous n'avez pas le droit d'accéder à cette page.");
}

$membre = false;

if (!empty($_GET['id']) && is_numeric($_GET['id']))
{
    $membre = $membres->get((int) $_GET['id']);

    if (!$membre)
    {
        throw new UserException("Ce membre n'existe pas.");
    }
}

$transactions = new Transactions;
$m_transactions = new Membres_Transactions;

$error = false;

if (!empty($_POST['add']))
{
    if (!utils::CSRF_check('add_transaction'))
    {
        $error = 'Une erreur est survenue, merci de renvoyer le formulaire.';
    }
    else
    {
        try {
            $m_transactions->add(array(
                'libelle'           =>  utils::post('libelle'),
                'date'              =>  utils::post('date'),
                'id_transaction'    =>  utils::post('id_transaction'),
                'montant'           =>  (float) utils::post('montant'),
                'id_membre'         =>  utils::post('id_membre'),
            ));

            utils::redirect('/admin/membres/transactions.php?id=' . (int)utils::post('id_membre'));
        }
        catch (UserException $e)
        {
            $error = $e->getMessage();
        }
    }
}

$tpl->assign('error', $error);
$tpl->assign('membre', $membre);

$tpl->assign('transactions', $transactions->listCurrent());

$tpl->assign('default_tr', null);
$tpl->assign('default_amount', 0.00);

if ($membre)
{
    $cats = new Membres_Categories;
    $categorie = $cats->get($membre['id_categorie']);

    if (!empty($categorie['id_transaction_obligatoire']))
    {
        $tr = $transactions->get($categorie['id_transaction_obligatoire']);

        $tpl->assign('default_tr', $tr['id']);
        $tpl->assign('default_amount', $tr['montant']);
    }
}

$tpl->display('admin/membres/transactions/ajout.tpl');

?>