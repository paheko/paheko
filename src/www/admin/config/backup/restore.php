<?php
namespace Paheko;

use Paheko\Backup;
use Paheko\Users\Session;

require_once __DIR__ . '/../_inc.php';

$code = null; // error code
$session = Session::getInstance();
$ok_code = qg('code'); // return code
$ok = qg('ok'); // return message

if ($ok === 'restore') {
	// Force user to be re-logged as the first admin if its user ID does not work
	if (!$session->refresh()) {
		$session->forceLogin(-1);
		$ok_code |= Backup::CHANGED_USER;
	}
}

if (qg('download')) {
	Backup::dump(qg('download'));
	exit;
}

$form->runIf('restore', function () use ($session) {
	if (!f('selected')) {
		throw new UserException('Aucune sauvegarde sélectionnée');
	}

	$r = Backup::restoreFromLocal(f('selected'), $session);
	Utils::redirect(Utils::getSelfURI(['ok' => 'restore', 'code' => (int)$r]));
}, 'backup_manage');

$form->runIf('remove', function () {
	if (!f('selected')) {
		throw new UserException('Aucune sauvegarde sélectionnée');
	}

	Backup::remove(f('selected'));
}, 'backup_manage', Utils::getSelfURI(['ok' => 'remove']));


$form->runIf('restore_file', function () use (&$code, $session, $form) {
	// Ignorer la vérification d'intégrité si autorisé et demandé
	$check = (ALLOW_MODIFIED_IMPORT && f('force_import')) ? false : true;

	try {
		$r = Backup::restoreFromUpload($_FILES['file'], $session, $check);
		Utils::redirect(Utils::getSelfURI(['ok' => 'restore', 'code' => (int)$r]));
	} catch (UserException $e) {
		$code = $e->getCode();
		if ($code === 0) {
			throw $e;
		}
		$form->addError($e->getMessage());
	}
}, 'backup_restore');

$list = Backup::list();
$size = Backup::getAllBackupsTotalSize();

$tpl->assign(compact('code', 'list', 'ok', 'ok_code', 'size'));

$tpl->display('config/backup/restore.tpl');
