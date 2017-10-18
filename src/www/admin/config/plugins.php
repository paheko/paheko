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
            Utils::redirect('/admin/config/plugins.php');
        }
        catch (UserException $e)
        {
            $form->addError($e->getMessage());
        }
    }
}

if (f('delete'))
{
    $form->check('delete_plugin_' . qg('delete'), [
        'plugin' => 'required',
    ]);

    if (!$form->hasErrors())
    {
        try {
            $plugin = new Plugin(qg('delete'));
            $plugin->uninstall();
            
            Utils::redirect('/admin/config/plugins.php');
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

$tpl->display('admin/config/plugins.tpl');
