<?php
namespace Garradin;

require_once __DIR__ . '/_inc.php';

$s = new Sauvegarde;
$error = false;

if (utils::post('config'))
{
    if (!utils::CSRF_check('backup_config'))
    {
        $error = 'Une erreur est survenue, merci de renvoyer le formulaire.';
    }
    else
    {
        $config->set('frequence_sauvegardes', utils::post('frequence_sauvegardes'));
        $config->set('nombre_sauvegardes', utils::post('nombre_sauvegardes'));
        $config->save();

        utils::redirect('/admin/config/donnees.php?ok=config');
    }
}
elseif (utils::post('create'))
{
    if (!utils::CSRF_check('backup_create'))
    {
        $error = 'Une erreur est survenue, merci de renvoyer le formulaire.';
    }
    else
    {
        $s->create();
        utils::redirect('/admin/config/donnees.php?ok=create');
    }
}
elseif (utils::post('download'))
{
    if (!utils::CSRF_check('backup_download'))
    {
        $error = 'Une erreur est survenue, merci de renvoyer le formulaire.';
    }
    else
    {
        header('Content-type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $config->get('nom_asso') . ' - Sauvegarde données - ' . date('Y-m-d') . '.sqlite"');

        $s->dump();
        exit;
    }
}
elseif (utils::post('restore'))
{
    if (!utils::CSRF_check('backup_manage'))
    {
        $error = 'Une erreur est survenue, merci de renvoyer le formulaire.';
    }
    else
    {
        $s->restoreFromLocal(utils::post('file'));
        utils::redirect('/admin/config/donnees.php?ok=restore');
    }
}
elseif (utils::post('remove'))
{
    if (!utils::CSRF_check('backup_manage'))
    {
        $error = 'Une erreur est survenue, merci de renvoyer le formulaire.';
    }
    else
    {
        $s->remove(utils::post('file'));
        utils::redirect('/admin/config/donnees.php?ok=remove');
    }
}

$tpl->assign('error', $error);
$tpl->assign('ok', utils::get('ok'));
$tpl->assign('liste', $s->getList());

$tpl->display('admin/config/donnees.tpl');

?>