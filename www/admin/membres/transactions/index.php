<?php
namespace Garradin;

require_once __DIR__ . '/../../_inc.php';

if ($user['droits']['membres'] < Membres::DROIT_ADMIN)
{
    throw new UserException("Vous n'avez pas le droit d'accéder à cette page.");
}

$transactions = new Transactions;

$error = false;

if (!empty($_POST['save']))
{
    if (!utils::CSRF_check('new_transaction'))
    {
        $error = 'Une erreur est survenue, merci de renvoyer le formulaire.';
    }
    else
    {
        try {
            $transactions->add(array(
                'intitule'          =>  utils::post('intitule'),
                'montant'           =>  (float) utils::post('montant'),
            ));

            utils::redirect('/admin/membres/transactions/');
        }
        catch (UserException $e)
        {
            $error = $e->getMessage();
        }
    }
}

$tpl->assign('error', $error);

$tpl->assign('liste', $transactions->listByName());

$tpl->display('admin/membres/transactions/index.tpl');

?>