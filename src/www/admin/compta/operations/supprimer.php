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

if (f('delete'))
{
    if ($form->check('compta_supprimer_' . $operation->id))
    {
        try
        {
            $journal->delete($operation->id);
            Utils::redirect('/admin/compta/operations/');
        }
        catch (UserException $e)
        {
            $form->addError($e->getMessage());
        }
    }
}

$tpl->assign('operation', $operation);

$tpl->display('admin/compta/operations/supprimer.tpl');