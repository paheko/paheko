<?php

namespace Garradin;

require_once __DIR__ . '/_inc.php';

$error = false;

if (!empty($_POST['install']))
{
    if (!utils::CSRF_check('install'))
    {
        $error = 'Une erreur est survenue, merci de renvoyer le formulaire.';
    }
    else
    {
        try {
            Plugin::install(utils::post('to_install'), false);
            
            utils::redirect('/admin/config/plugins.php');
        }
        catch (\Exception $e)
        {
            $error = $e->getMessage();
        }
    }
}

$tpl->assign('error', $error);

$tpl->assign('liste_telecharges', Plugin::listDownloaded());
$tpl->assign('liste_installes', Plugin::listInstalled());

$tpl->display('admin/config/plugins.tpl');

?>