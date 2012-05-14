<?php

require_once __DIR__ . '/../_inc.php';

if ($user['droits']['config'] < Garradin_Membres::DROIT_ADMIN)
{
    throw new UserException("Vous n'avez pas le droit d'accéder à cette page.");
}

$error = false;

if (isset($_GET['ok']))
{
    $error = 'OK';
}

if (!empty($_POST['save']))
{
    if (!utils::CSRF_check('config_site'))
    {
        $error = 'Une erreur est survenue, merci de renvoyer le formulaire.';
    }
    else
    {
        try {
            $config->set('champs_obligatoires', utils::post('champs_obligatoires'));
            $config->set('champs_modifiables_membre', utils::post('champs_modifiables_membre'));
            $config->set('categorie_membres', utils::post('categorie_membres'));
            $config->save();

            utils::redirect('/admin/config/site.php?ok');
        }
        catch (UserException $e)
        {
            $error = $e->getMessage();
        }
    }
}

$tpl->assign('error', $error);
$tpl->display('admin/config/site.tpl');

?>