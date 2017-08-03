<?php
namespace Garradin;

require_once __DIR__ . '/_inc.php';

$session->requireAccess('membres', Membres::DROIT_ADMIN);

qv(['id' => 'required|numeric']);

$membre = $membres->get(qg('id'));

if (!$membre)
{
    throw new UserException("Ce membre n'existe pas.");
}

if ($membre->id == $user->id)
{
    throw new UserException("Il n'est pas possible de supprimer votre propre compte.");
}

if (f('delete'))
{
    $form->check('delete_membre_'.$membre->id);

    if (!$form->hasErrors())
    {
        try {
            $membres->delete($membre->id);
            Utils::redirect('/admin/membres/');
        }
        catch (UserException $e)
        {
            $form->addError($e->getMessage());
        }
    }
}

$tpl->assign('membre', $membre);

$tpl->display('admin/membres/supprimer.tpl');
