<?php
namespace Garradin;

require_once __DIR__ . '/../../../_inc.php';

if ($user['droits']['membres'] < Membres::DROIT_ADMIN)
{
    throw new UserException("Vous n'avez pas le droit d'accéder à cette page.");
}

if (!utils::get('id') || !is_numeric(utils::get('id')))
{
    throw new UserException("Argument du numéro de rappel manquant.");
}

$rappels = new Rappels;

$rappel = $rappels->get(utils::get('id'));

if (!$rappel)
{
    throw new UserException("Ce rappel n'existe pas.");
}

$error = false;

if (!empty($_POST['delete']))
{
    if (!utils::CSRF_check('delete_rappel_' . $rappel['id']))
    {
        $error = 'Une erreur est survenue, merci de renvoyer le formulaire.';
    }
    else
    {
        try {
            $rappels->delete($rappel['id'], (bool) utils::post('delete_history'));
            utils::redirect('/admin/membres/cotisations/gestion/rappels.php');
        }
        catch (UserException $e)
        {
            $error = $e->getMessage();
        }
    }
}

$tpl->assign('error', $error);

$tpl->assign('rappel', $rappel);

$tpl->display('admin/membres/cotisations/gestion/rappel_supprimer.tpl');

?>