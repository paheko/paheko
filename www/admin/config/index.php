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
    if (!utils::CSRF_check('config'))
    {
        $error = 'Une erreur est survenue, merci de renvoyer le formulaire.';
    }
    else
    {
        try {
            $config->set('nom_asso', utils::post('nom_asso'));
            $config->set('email_asso', utils::post('email_asso'));
            $config->set('adresse_asso', utils::post('adresse_asso'));
            $config->set('site_asso', utils::post('site_asso'));
            $config->set('email_envoi_automatique', utils::post('email_envoi_automatique'));
            $config->set('accueil_wiki', utils::post('accueil_wiki'));

            $config->set('pays', utils::post('pays'));
            $config->set('monnaie', utils::post('monnaie'));

            $config->save();

            utils::redirect('/admin/config/?ok');
        }
        catch (UserException $e)
        {
            $error = $e->getMessage();
        }
    }
}

$tpl->assign('error', $error);

$tpl->assign('garradin_version', garradin_version() . ' [' . garradin_manifest() . ']');
$tpl->assign('pays', utils::getCountryList());

$tpl->display('admin/config/index.tpl');

?>