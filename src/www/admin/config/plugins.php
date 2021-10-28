<?php

namespace Garradin;

require_once __DIR__ . '/_inc.php';

if (f('install'))
{
    $form->check('install_plugin', [
        'plugin' => 'required',
    ]);

    if (!$form->hasErrors())
    {
        try {
            Plugin::install(f('plugin'), false);
            $session->set('plugins_menu', null);
            Utils::redirect(ADMIN_URL . 'config/plugins.php');
        }
        catch (UserException $e)
        {
            $form->addError($e->getMessage());
        }
    }
}

if (f('delete'))
{
    $form->check('delete_plugin_' . qg('delete'));

    if (!$form->hasErrors())
    {
        try {
            $plugin = new Plugin(qg('delete'));
            $plugin->uninstall();
            $session->set('plugins_menu', null);

            Utils::redirect(ADMIN_URL . 'config/plugins.php');
        }
        catch (UserException $e)
        {
            $form->addError($e->getMessage());
        }
    }
}

if (qg('delete'))
{
    $plugin = new Plugin(qg('delete'));
    $tpl->assign('plugin', $plugin->getInfos());
    $tpl->assign('delete', true);
}
else
{
    $tpl->assign('liste_telecharges', Plugin::listDownloaded());
    $tpl->assign('liste_installes', Plugin::listInstalled());
}

$tpl->assign('garradin_website', WEBSITE);

$tpl->display('admin/config/plugins.tpl');

Plugin::upgradeAllIfRequired();
