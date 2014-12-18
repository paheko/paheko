<?php
namespace Garradin;

require_once __DIR__ . '/../_inc.php';

if ($user['droits']['compta'] < Membres::DROIT_ADMIN)
{
    throw new UserException("Vous n'avez pas le droit d'accéder à cette page.");
}

$id = Utils::get('id');
$compte = $comptes->get($id);

if (!$compte)
{
    throw new UserException('Le compte demandé n\'existe pas.');
}

$error = false;

if (!empty($_POST['delete']))
{
    if (!Utils::CSRF_check('compta_delete_compte_'.$compte['id']))
    {
        $error = 'Une erreur est survenue, merci de renvoyer le formulaire.';
    }
    else
    {
        try
        {
            $comptes->delete($compte['id']);
            Utils::redirect('/admin/compta/comptes/?classe='.substr($compte['id'], 0, 1));
        }
        catch (UserException $e)
        {
            $error = $e->getMessage();
        }
    }
}
elseif (!empty($_POST['disable']))
{
    if (!Utils::CSRF_check('compta_disable_compte_'.$compte['id']))
    {
        $error = 'Une erreur est survenue, merci de renvoyer le formulaire.';
    }
    else
    {
        try
        {
            $comptes->disable($compte['id']);
            Utils::redirect('/admin/compta/comptes/?classe='.substr($compte['id'], 0, 1));
        }
        catch (UserException $e)
        {
            $error = $e->getMessage();
        }
    }
}

$tpl->assign('can_delete', $comptes->canDelete($compte['id']));
$tpl->assign('can_disable', $comptes->canDisable($compte['id']));

$tpl->assign('error', $error);

$tpl->assign('compte', $compte);

$tpl->display('admin/compta/comptes/supprimer.tpl');

?>