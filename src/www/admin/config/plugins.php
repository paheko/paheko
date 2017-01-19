<?php

namespace Garradin;

require_once __DIR__ . '/_inc.php';

$error = false;

if (!empty($_POST['install']))
{
    if (!Utils::CSRF_check('install_plugin'))
    {
        $error = 'Une erreur est survenue, merci de renvoyer le formulaire.';
    }
    elseif (trim(Utils::post('to_install')) === '')
    {
        $error = 'Aucun plugin sélectionné.';
    }
    else
    {
        try {
            Plugin::install(Utils::post('to_install'), false);
            
            Utils::redirect('/admin/config/plugins.php');
        }
        catch (UserException $e)
        {
            $error = $e->getMessage();
        }
    }
}

if (Utils::post('delete'))
{
    if (!Utils::CSRF_check('delete_plugin_' . Utils::get('delete')))
    {
        $error = 'Une erreur est survenue, merci de renvoyer le formulaire.';
    }
    else
    {
        try {
            $plugin = new Plugin(Utils::get('delete'));
            $plugin->uninstall();
            
            Utils::redirect('/admin/config/plugins.php');
        }
        catch (UserException $e)
        {
            $error = $e->getMessage();
        }
    }
}

$tpl->assign('error', $error);

if (Utils::get('delete'))
{
    $plugin = new Plugin(Utils::get('delete'));
    $tpl->assign('plugin', $plugin->getInfos());
    $tpl->assign('delete', true);
}
else
{
    $tpl->assign('liste_telecharges', Plugin::listDownloaded());
    $tpl->assign('liste_installes', Plugin::listInstalled());
}

$tpl->display('admin/config/plugins.tpl');
