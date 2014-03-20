<?php

namespace Garradin;

require_once __DIR__ . '/_inc.php';

$error = false;

if (!empty($_POST['install']))
{
    if (!utils::CSRF_check('install_plugin'))
    {
        $error = 'Une erreur est survenue, merci de renvoyer le formulaire.';
    }
    else
    {
        try {
            Plugin::install(utils::post('to_install'), false);
            
            utils::redirect('/admin/config/plugins.php');
        }
        catch (UserException $e)
        {
            $error = $e->getMessage();
        }
    }
}

if (utils::post('delete'))
{
    if (!utils::CSRF_check('delete_plugin_' . utils::get('delete')))
    {
        $error = 'Une erreur est survenue, merci de renvoyer le formulaire.';
    }
    else
    {
        try {
            $plugin = new Plugin(utils::get('delete'));
            $plugin->uninstall();
            
            utils::redirect('/admin/config/plugins.php');
        }
        catch (UserException $e)
        {
            $error = $e->getMessage();
        }
    }
}

$tpl->assign('error', $error);

if (utils::get('delete'))
{
    $plugin = new Plugin(utils::get('delete'));
    $tpl->assign('plugin', $plugin->getInfos());
    $tpl->assign('delete', true);
}
else
{
    $tpl->assign('liste_telecharges', Plugin::listDownloaded());
    $tpl->assign('liste_installes', Plugin::listInstalled());
}

$tpl->display('admin/config/plugins.tpl');

?>