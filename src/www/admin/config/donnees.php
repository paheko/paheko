<?php
namespace Garradin;

require_once __DIR__ . '/_inc.php';

$s = new Sauvegarde;
$code = $error = false;

if (Utils::post('config'))
{
    if (!Utils::CSRF_check('backup_config'))
    {
        $error = 'Une erreur est survenue, merci de renvoyer le formulaire.';
    }
    else
    {
        try {
            $config->set('frequence_sauvegardes', Utils::post('frequence_sauvegardes'));
            $config->set('nombre_sauvegardes', Utils::post('nombre_sauvegardes'));
            $config->save();

            Utils::redirect('/admin/config/donnees.php?ok=config');
        } catch (UserException $e) {
            $error = $e->getMessage();
        }
    }
}
elseif (Utils::post('create'))
{
    if (!Utils::CSRF_check('backup_create'))
    {
        $error = 'Une erreur est survenue, merci de renvoyer le formulaire.';
    }
    else
    {
        try {
            $s->create();
            Utils::redirect('/admin/config/donnees.php?ok=create');
        } catch (UserException $e) {
            $error = $e->getMessage();
        }
    }
}
elseif (Utils::post('download'))
{
    if (!Utils::CSRF_check('backup_download'))
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
elseif (Utils::post('restore'))
{
    if (!Utils::CSRF_check('backup_manage'))
    {
        $error = 'Une erreur est survenue, merci de renvoyer le formulaire.';
    }
    else
    {
        try {
            $r = $s->restoreFromLocal(Utils::post('file'));
            Utils::redirect('/admin/config/donnees.php?ok=restore&code=' . (int)$r);
        } catch (UserException $e) {
            $error = $e->getMessage();
        }
    }
}
elseif (Utils::post('remove'))
{
    if (!Utils::CSRF_check('backup_manage'))
    {
        $error = 'Une erreur est survenue, merci de renvoyer le formulaire.';
    }
    else
    {
        try {
            $s->remove(Utils::post('file'));
            Utils::redirect('/admin/config/donnees.php?ok=remove');
        } catch (UserException $e) {
            $error = $e->getMessage();
        }
    }
}
elseif (Utils::post('restore_file'))
{
    if (!Utils::CSRF_check('backup_restore'))
    {
        $error = 'Une erreur est survenue, merci de renvoyer le formulaire.';
    }
    else
    {
        // Ignorer la vérification d'intégrité si autorisé et demandé
        $check = (ALLOW_MODIFIED_IMPORT && Utils::post('force_import')) ? false : true;

        try {
            $r = $s->restoreFromUpload($_FILES['file'], $user->id, $check);
            Utils::redirect('/admin/config/donnees.php?ok=restore&code=' . (int)$r);
        } catch (UserException $e) {
            $error = $e->getMessage();
            $code = $e->getCode();
        }
    }
}

$tpl->assign('error', $error);
$tpl->assign('code', $code);
$tpl->assign('ok_code', Utils::get('code'));
$tpl->assign('ok', Utils::get('ok'));
$tpl->assign('liste', $s->getList());
$tpl->assign('max_file_size', Utils::getMaxUploadSize());

$tpl->assign('db_size', $s->getDBSize());
$tpl->assign('files_size', $s->getDBFilesSize());

$tpl->display('admin/config/donnees.tpl');
