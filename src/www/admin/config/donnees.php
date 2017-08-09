<?php
namespace Garradin;

require_once __DIR__ . '/_inc.php';

$s = new Sauvegarde;
$code = false;

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

            Utils::redirect('/admin/config/donnees.php?ok=config');
        } catch (UserException $e) {
            $form->addError($e->getMessage());
        }
    }
}
elseif (f('create'))
{
    $form->check('backup_create');

    if (!$form->hasErrors())
    {
        try {
            $s->create();
            Utils::redirect('/admin/config/donnees.php?ok=create');
        } catch (UserException $e) {
            $form->addError($e->getMessage());
        }
    }
}
elseif (f('download'))
{
    $form->check('backup_download');

    if (!$form->hasErrors())
    {
        header('Content-type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $config->get('nom_asso') . ' - Sauvegarde données - ' . date('Y-m-d') . '.sqlite"');
        header('Content-Length: ' . $s->getDBSize());

        $s->dump();
        exit;
    }
}
elseif (f('restore'))
{
    $form->check('backup_manage');

    if (!$form->hasErrors())
    {
        try {
            $r = $s->restoreFromLocal(f('file'));
            Utils::redirect('/admin/config/donnees.php?ok=restore&code=' . (int)$r);
        } catch (UserException $e) {
            $form->addError($e->getMessage());
        }
    }
}
elseif (f('remove'))
{
    $form->check('backup_manage');

    if (!$form->hasErrors())
    {
        try {
            $s->remove(f('file'));
            Utils::redirect('/admin/config/donnees.php?ok=remove');
        } catch (UserException $e) {
            $form->addError($e->getMessage());
        }
    }
}
elseif (f('restore_file'))
{
    $form->check('backup_restore');

    if (!$form->hasErrors())
    {
        // Ignorer la vérification d'intégrité si autorisé et demandé
        $check = (ALLOW_MODIFIED_IMPORT && f('force_import')) ? false : true;

        try {
            $r = $s->restoreFromUpload($_FILES['file'], $user->id, $check);
            Utils::redirect('/admin/config/donnees.php?ok=restore&code=' . (int)$r);
        } catch (UserException $e) {
            $form->addError($e->getMessage());
            $code = $e->getCode();
        }
    }
}

$tpl->assign('code', $code);
$tpl->assign('ok_code', qg('code'));
$tpl->assign('ok', qg('ok'));
$tpl->assign('liste', $s->getList());
$tpl->assign('max_file_size', Utils::getMaxUploadSize());

$tpl->assign('db_size', $s->getDBSize());
$tpl->assign('files_size', $s->getDBFilesSize());

$tpl->display('admin/config/donnees.tpl');
