<?php

require_once __DIR__ . '/_inc.php';

if ($user['droits']['compta'] < Garradin_Membres::DROIT_ADMIN)
{
    throw new UserException("Vous n'avez pas le droit d'accéder à cette page.");
}

require_once GARRADIN_ROOT . '/include/class.compta_journal.php';
$journal = new Garradin_Compta_Journal;

$operation = $journal->get(utils::get('id'));

if (!$operation)
{
    throw new UserException("L'opération demandée n'existe pas.");
}

$error = false;

if (!empty($_POST['delete']))
{
    if (!utils::CSRF_check('compta_supprimer_'.$operation['id']))
    {
        $error = 'Une erreur est survenue, merci de renvoyer le formulaire.';
    }
    else
    {
        try
        {
            $journal->delete($operation['id']);
            utils::redirect('/admin/compta/gestion.php');
        }
        catch (UserException $e)
        {
            $error = $e->getMessage();
        }
    }
}

$tpl->assign('error', $error);

$tpl->assign('operation', $operation);

$tpl->display('admin/compta/operation_supprimer.tpl');

?>