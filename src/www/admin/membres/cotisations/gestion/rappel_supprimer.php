<?php
namespace Garradin;

require_once __DIR__ . '/../../_inc.php';

$session->requireAccess('membres', Membres::DROIT_ADMIN);

if (!qg('id') || !is_numeric(qg('id')))
{
    throw new UserException("Argument du numéro de rappel manquant.");
}

$rappels = new Rappels;

$rappel = $rappels->get(qg('id'));

if (!$rappel)
{
    throw new UserException("Ce rappel n'existe pas.");
}

if (f('delete') && $form->check('delete_rappel_' . $rappel->id))
{
    try {
        $rappels->delete($rappel->id, (bool) f('delete_history'));
        Utils::redirect('/admin/membres/cotisations/gestion/rappels.php');
    }
    catch (UserException $e)
    {
        $form->addError($e->getMessage());
    }
}

$tpl->assign('rappel', $rappel);

$tpl->display('admin/membres/cotisations/gestion/rappel_supprimer.tpl');
