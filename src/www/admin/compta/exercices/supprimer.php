<?php
namespace Garradin;

require_once __DIR__ . '/../_inc.php';

$session->requireAccess('compta', Membres::DROIT_ADMIN);

$e = new Compta\Exercices;

$exercice = $e->get((int)qg('id'), true);

if (!$exercice)
{
	throw new UserException('Exercice inconnu.');
}

if ($exercice->cloture && $exercice->nb_operations > 0)
{
    throw new UserException('Impossible de supprimer un exercice clôturé.');
}

if (f('delete'))
{
    if ($form->check('compta_supprimer_exercice_'.$exercice->id))
    {
        try
        {
            $id = $e->delete($exercice->id);

            Utils::redirect('/admin/compta/exercices/');
        }
        catch (UserException $e)
        {
            $form->addError($e->getMessage());
        }
    }
}

$tpl->assign('exercice', $exercice);

$tpl->display('admin/compta/exercices/supprimer.tpl');
