<?php
namespace Garradin;

require_once __DIR__ . '/../_inc.php';

if ($user['droits']['membres'] < Membres::DROIT_ADMIN)
{
    throw new UserException("Vous n'avez pas le droit d'accéder à cette page.");
}

$membre = $membres->get(Utils::get('id'));

if (!$membre)
{
    throw new UserException("Ce membre n'existe pas.");
}

$error = false;

if ($membre['id'] == $user['id'])
{
    $error = "Il n'est pas possible de supprimer votre propre compte.";
}

if (Utils::post('delete') && !$error)
{
    if (!Utils::CSRF_check('delete_membre_'.$membre['id']))
    {
        $error = 'Une erreur est survenue, merci de renvoyer le formulaire.';
    }
    else
    {
        try {
            $membres->delete($membre['id']);
            Utils::redirect('/admin/membres/');
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