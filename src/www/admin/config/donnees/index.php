<?php
namespace Garradin;

require_once __DIR__ . '/../_inc.php';

$s = new Sauvegarde;

$tpl->assign('code', null);

$form->runIf('download', function () use ($s) {
    $s->dump();
    exit;
}, 'backup_download');

$form->runIf('restore_file', function () use ($s, $tpl, $session) {
    // Ignorer la vérification d'intégrité si autorisé et demandé
    $check = (ALLOW_MODIFIED_IMPORT && f('force_import')) ? false : true;

    try {
        $r = $s->restoreFromUpload($_FILES['file'], $session->getUser()->id, $check);
        Utils::redirect(ADMIN_URL . 'config/donnees/?ok=restore&code=' . (int)$r);
    } catch (UserException $e) {
        $form->addError($e->getMessage());
        $tpl->assign('code', $e->getCode());
    }
}, 'backup_restore');

$tpl->assign('db_size', $s->getDBSize());
$tpl->assign('files_size', $s->getDBFilesSize());

$tpl->assign('ok_code', qg('code'));
$tpl->assign('ok', qg('ok'));
$tpl->assign('now_date', date('Y-m-d'));

$tpl->assign('max_file_size', Utils::getMaxUploadSize());

$tpl->display('admin/config/donnees/index.tpl');
