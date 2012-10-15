<?php

require_once __DIR__ . '/../_inc.php';

if ($user['droits']['membres'] < Garradin_Membres::DROIT_ADMIN)
{
    throw new UserException("Vous n'avez pas le droit d'accéder à cette page.");
}

$membre = $membres->get(utils::get('id'));

if (!$membre)
{
    throw new UserException("Ce membre n'existe pas.");
}

$error = false;

if (utils::post('delete'))
{
    if (!utils::CSRF_check('delete_membre_'.$membre['id']))
    {
        $error = 'Une erreur est survenue, merci de renvoyer le formulaire.';
    }
    else
    {
        try {
            $membres->delete($membre['id']);
            utils::redirect('/admin/membres/');
        }
        catch (UserException $e)
        {
            $error = $e->getMessage();
        }
    }
}

$tpl->assign('membre', $membre);
$tpl->assign('error', $error);

$tpl->display('admin/membres/supprimer.tpl');

?>