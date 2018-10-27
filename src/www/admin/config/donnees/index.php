<?php
namespace Garradin;

require_once __DIR__ . '/../_inc.php';

$s = new Sauvegarde;

if (f('download'))
{
    $form->check('backup_download');

    if (!$form->hasErrors())
    {
        header('Content-type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $config->get('nom_asso') . ' - Sauvegarde données - ' . date('Y-m-d') . '.sqlite"');
        header('Content-Length: ' . $s->getDBSize(true));

        $s->dump();
        exit;
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
            Utils::redirect(ADMIN_URL . 'config/donnees/?ok=restore&code=' . (int)$r);
        } catch (UserException $e) {
            $form->addError($e->getMessage());
            $code = $e->getCode();
        }
    }
}

$tpl->assign('db_size', $s->getDBSize());
$tpl->assign('files_size', $s->getDBFilesSize());

$tpl->assign('code', isset($code) ? $code : null);
$tpl->assign('ok_code', qg('code'));
$tpl->assign('ok', qg('ok'));
$tpl->assign('now_date', date('Y-m-d'));

$tpl->assign('max_file_size', Utils::getMaxUploadSize());

$tpl->display('admin/config/donnees/index.tpl');
