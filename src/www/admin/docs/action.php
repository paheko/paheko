<?php

namespace Paheko;

use Paheko\Accounting\Transactions;
use Paheko\Files\Files;
use Paheko\Files\Trash;
use Paheko\Entities\Files\File;

// Don't restrict access
require_once __DIR__ . '/../_inc.php';

$check = f('check');
$action = f('action');
$parent = f('parent');

$actions = ['move', 'delete', 'zip', 'move_to_transaction'];

if (!is_array($check) || !count($check) || !in_array($action, $actions)) {
	throw new UserException('Action invalide: ' . $action);
}

$csrf_key = 'docs_action_' . $action;

$count = count($check);

$extra = compact('parent', 'action', 'check');
$tpl->assign(compact('csrf_key', 'extra', 'action', 'count', 'check'));

if ($action == 'delete') {
	$form->runIf('delete', function () use ($check) {
		$files = [];

		foreach ($check as $path) {
			$file = Files::get($path);

			if (!$file) {
				continue;
			}

			if (!$file->canMoveToTrash()) {
				throw new UserException(sprintf('Vous n\'avez pas le droit de mettre ce fichier à la corbeille : %s', $file->path));
			}

			$files[] = $file;
		}

		foreach ($files as $file) {
			$file->moveToTrash();
		}
	}, $csrf_key, '!docs/?path=' . $parent);

	$tpl->display('docs/action_delete.tpl');
}
elseif ($action == 'zip') {
	$form->runIf('zip', function() use ($check, $session) {
		Files::zip($check, null, $session);
		exit;
	}, $csrf_key);

	$size = 0;

	foreach ($check as $selected) {
		$file = Files::get($selected);

		if (!$file || !$file->canRead($session)) {
			continue;
		}

		$size += $file->getRecursiveSize();
	}

	$tpl->assign(compact('size'));
	$tpl->display('docs/action_zip.tpl');
}
elseif ($action === 'move_to_transaction') {
	$session->requireAccess($session::SECTION_ACCOUNTING, $session::ACCESS_WRITE);

	$form->runIf('move', function() use ($check, $session) {
		$id = (int)f('id');
		if (!Transactions::get($id)) {
			throw new UserException('Cette écriture n\'existe pas.');
		}

		$files = [];

		foreach ($check as $path) {
			$file = Files::get($path);

			if (!$file) {
				continue;
			}

			if (!$file->canMoveTo(File::CONTEXT_TRANSACTION . '/' . $id)) {
				throw new UserException(sprintf('Vous n\'avez pas le droit de déplacer ce fichier : %s', $file->path));
			}

			$files[] = $file;
		}

		foreach ($files as $file) {
			$file->move(File::CONTEXT_TRANSACTION . '/' . $id);
		}

		Utils::redirectParent('!acc/transactions/details.php?id=' . $id);
	}, $csrf_key);


	$tpl->display('docs/action_move_to_transaction.tpl');
}
else {
	$move_list = $check;
	require __DIR__ . '/_move.php';
}
