<?php

namespace Paheko;

use Paheko\Files\Files;
use Paheko\Files\Trash;
use Paheko\Users\Session;
use Paheko\Entities\Files\File;

require_once __DIR__ . '/../_inc.php';

$session = Session::getInstance();

// Allow access to documents administrators or config administrators
if (!$session->canAccess($session::SECTION_CONFIG, $session::ACCESS_ADMIN)) {
	$session->requireAccess($session::SECTION_DOCUMENTS, $session::ACCESS_ADMIN);
}

$csrf_key = 'trash_action';
$check = f('check');
$extra = compact('check');
$count = $check ? count($check) : null;

$tpl->assign(compact('csrf_key', 'extra', 'count'));

$form->runIf('confirm_delete', function () use ($check) {
	if (empty($check)) {
		throw new UserException('Aucun fichier sélectionné');
	}

	foreach ($check as &$file) {
		$file = Files::getByHashID($file);

		if (!$file) {
			continue;
		}

		if (!$file->canDelete()) {
			throw new UserException('Impossible de supprimer un fichier car vous n\'avez pas le droit de le supprimer');
		}
	}

	unset($file);

	$db = DB::getInstance();
	$db->begin();

	foreach ($check as $file) {
		if ($file === null) {
			continue;
		}

		$file->delete();
	}

	Files::pruneEmptyDirectories(File::CONTEXT_TRASH);

	$db->commit();
}, $csrf_key, '!docs/trash.php');

$form->runIf('restore', function() use ($check) {
	if (empty($check)) {
		throw new UserException('Aucun fichier sélectionné');
	}

	foreach ($check as &$file) {
		$file = Files::getByHashID($file);

		if (!$file) {
			throw new UserException('Impossible de restaurer un fichier qui n\'existe plus');
		}
	}

	unset($file);

	$db = DB::getInstance();
	$db->begin();

	foreach ($check as $file) {
		$file->restoreFromTrash();
	}

	Files::pruneEmptyDirectories(File::CONTEXT_TRASH);

	$db->commit();

}, $csrf_key, '!docs/trash.php');

if (f('delete')) {
	$tpl->display('docs/trash_delete.tpl');
}
else {
	Trash::clean();

	$size = Trash::getSize();
	$list = Trash::list();
	$list->loadFromQueryString();

	$tpl->assign(compact('list', 'size'));

	$tpl->display('docs/trash.tpl');
}
