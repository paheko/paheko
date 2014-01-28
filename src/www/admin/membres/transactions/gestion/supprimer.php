<?php
namespace Garradin;

require_once __DIR__ . '/../../../_inc.php';

if ($user['droits']['membres'] < Membres::DROIT_ADMIN)
{
    throw new UserException("Vous n'avez pas le droit d'accéder à cette page.");
}

$transactions = new Transactions;

$tr = $transactions->get(utils::get('id'));

if (!$tr)
{
    throw new UserException("Cette transaction n'existe pas.");
}

$error = false;

if (!empty($_POST['delete']))
{
    if (!utils::CSRF_check('delete_tr_' . $tr['id']))
    {
        $error = 'Une erreur est survenue, merci de renvoyer le formulaire.';
    }
    else
    {
        try {
            $transactions->delete($tr['id']);
            utils::redirect('/admin/membres/transactions/gestion/');
        }
        catch (UserException $e)
        {
            $error = $e->getMessage();
        }
    }
}

$tpl->assign('error', $error);

$tpl->assign('transaction', $tr);

$tpl->display('admin/membres/transactions/gestion/supprimer.tpl');

?>