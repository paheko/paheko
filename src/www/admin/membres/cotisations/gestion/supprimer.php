<?php
namespace Garradin;

require_once __DIR__ . '/../../_inc.php';

$session->requireAccess('membres', Membres::DROIT_ADMIN);

if (!qg('id') || !is_numeric(qg('id')))
{
    throw new UserException("Argument du numéro de cotisation manquant.");
}

$cotisations = new Cotisations;

$co = $cotisations->get(qg('id'));

if (!$co)
{
    throw new UserException("Cette cotisation n'existe pas.");
}

if (f('delete') && $form->check('delete_co_' . $co->id))
{
    try {
        $cotisations->delete($co->id);
        Utils::redirect('/admin/membres/cotisations/');
    }
    catch (UserException $e)
    {
        $form->addError($e->getMessage());
    }
}

$tpl->assign('cotisation', $co);

$tpl->display('admin/membres/cotisations/gestion/supprimer.tpl');
