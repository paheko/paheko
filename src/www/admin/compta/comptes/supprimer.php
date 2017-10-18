<?php
namespace Garradin;

require_once __DIR__ . '/../_inc.php';

$session->requireAccess('compta', Membres::DROIT_ADMIN);

$id = qg('id');
$compte = $comptes->get($id);

if (!$compte)
{
    throw new UserException('Le compte demandé n\'existe pas.');
}

if (f('delete') && $form->check('compta_delete_compte_' . $compte->id))
{
    try
    {
        $comptes->delete($compte->id);
        Utils::redirect('/admin/compta/comptes/?classe=' . substr($compte->id, 0, 1));
    }
    catch (UserException $e)
    {
        $form->addError($e->getMessage());
    }
}
elseif (f('disable') && $form->check('compta_disable_compte_' . $compte->id))
{
    try
    {
        $comptes->disable($compte->id);
        Utils::redirect('/admin/compta/comptes/?classe='.substr($compte->id, 0, 1));
    }
    catch (UserException $e)
    {
        $form->addError($e->getMessage());
    }
}

$tpl->assign('can_delete', $comptes->canDelete($compte->id));
$tpl->assign('can_disable', $comptes->canDisable($compte->id));

$tpl->assign('compte', $compte);

$tpl->display('admin/compta/comptes/supprimer.tpl');
