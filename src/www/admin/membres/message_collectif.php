<?php
namespace Garradin;

require_once __DIR__ . '/../_inc.php';

if ($user['droits']['membres'] < Membres::DROIT_ADMIN)
{
    throw new UserException("Vous n'avez pas le droit d'accéder à cette page.");
}

$error = false;

if (!empty($_POST['save']))
{
    if (!utils::CSRF_check('send_message_collectif'))
    {
        $error = 'Une erreur est survenue, merci de renvoyer le formulaire.';
    }
    elseif (!utils::post('sujet'))
    {
        $error = 'Le sujet ne peut rester vide.';
    }
    elseif (!utils::post('message'))
    {
        $error = 'Le message ne peut rester vide.';
    }
    else
    {
        try {
            $membres->sendMessageToCategory(utils::post('dest'), utils::post('sujet'), utils::post('message'), (bool) utils::post('subscribed'));
            utils::redirect('/admin/membres/?sent');
        }
        catch (UserException $e)
        {
            $error = $e->getMessage();
        }
    }
}

$cats = new Membres_Categories;

$tpl->assign('cats_liste', $cats->listSimple());
$tpl->assign('cats_cachees', $cats->listHidden());
$tpl->assign('error', $error);

$tpl->display('admin/membres/message_collectif.tpl');

?>