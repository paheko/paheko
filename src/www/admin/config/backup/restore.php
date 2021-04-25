<?php
namespace Garradin;

require_once __DIR__ . '/../_inc.php';

$s = new Sauvegarde;
$code = null; // error code

if (qg('download')) {
	$s->dump(qg('download'));
	exit;
}

$form->runIf('restore', function () use ($s) {
	if (!f('selected')) {
		throw new UserException('Aucune sauvegarde sélectionnée');
	}

	$r = $s->restoreFromLocal(f('selected'));
	Utils::redirect(Utils::getSelfURI(['ok' => 'restore', 'code' => (int)$r]));
}, 'backup_manage');

$form->runIf('remove', function () use ($s) {
	if (!f('selected')) {
		throw new UserException('Aucune sauvegarde sélectionnée');
	}

	$s->remove(f('selected'));
}, 'backup_manage', Utils::getSelfURI(['ok' => 'remove']));


$form->runIf('restore_file', function () use ($s, &$code, $session) {
	// Ignorer la vérification d'intégrité si autorisé et demandé
	$check = (ALLOW_MODIFIED_IMPORT && f('force_import')) ? false : true;

	try {
		$r = $s->restoreFromUpload($_FILES['file'], $session->getUser()->id, $check);
		Utils::redirect(Utils::getSelfURI(['ok' => 'restore', 'code' => (int)$r]));
	} catch (UserException $e) {
		$code = $e->getCode();
	}
}, 'backup_restore');


$ok_code = qg('code'); // return code
$ok = qg('ok'); // return message

$list = $s->getList();

$tpl->assign(compact('code', 'list', 'ok', 'ok_code'));

$tpl->display('admin/config/backup/restore.tpl');
