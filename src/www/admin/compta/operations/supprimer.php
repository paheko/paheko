<?php
namespace Garradin;

require_once __DIR__ . '/../_inc.php';

$session->requireAccess('compta', Membres::DROIT_ADMIN);

$journal = new Compta\Journal;

$operation = $journal->get(qg('id'));

if (!$operation)
{
    throw new UserException("L'opération demandée n'existe pas.");
}

$error = false;

if (f('delete'))
{
    if (!Utils::CSRF_check('compta_supprimer_'.$operation['id']))
    {
        $error = 'Une erreur est survenue, merci de renvoyer le formulaire.';
    }
    else
    {
        try
        {
            $journal->delete($operation->id);
            Utils::redirect('/admin/compta/operations/');
        }
        catch (UserException $e)
        {
            $error = $e->getMessage();
        }
    }
}

$tpl->assign('error', $error);

$tpl->assign('operation', $operation);

$tpl->display('admin/compta/operations/supprimer.tpl');