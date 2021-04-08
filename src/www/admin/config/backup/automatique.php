<?php
namespace Garradin;

require_once __DIR__ . '/../_inc.php';

if (!ENABLE_AUTOMATIC_BACKUPS)
{
    throw new UserException('Les sauvegardes automatiques sont désactivées.');
}

if (f('config'))
{
    $form->check('backup_config', [
        'frequence_sauvegardes' => 'present|numeric|min:0|max:365',
        'nombre_sauvegardes' => 'present|numeric|min:1|max:90',
    ]);

    if (!$form->hasErrors())
    {
        try {
            $config->set('frequence_sauvegardes', f('frequence_sauvegardes'));
            $config->set('nombre_sauvegardes', f('nombre_sauvegardes'));
            $config->save();

            Utils::redirect(ADMIN_URL . 'config/donnees/automatique.php?ok=config');
        } catch (UserException $e) {
            $form->addError($e->getMessage());
        }
    }
}

$tpl->assign('ok', qg('ok'));

$tpl->display('admin/config/donnees/automatique.tpl');
