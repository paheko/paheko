<?php

namespace Garradin;

use Garradin\Files\Files;
use Garradin\Entities\Files\File;

require_once __DIR__ . '/_inc.php';

$check = f('check');
$action = f('action');
$parent = f('parent');

$actions = ['move', 'delete'];

if (!is_array($check) || !count($check) || !in_array($action, $actions)) {
	throw new UserException('Action invalide');
}

$csrf_key = 'action_' . $action;

$form->runIf('confirm_delete', function () use ($check, $session) {
	foreach ($check as &$file) {
		$file = Files::get($file);

		if (!$file || !$file->checkDeleteAccess($session)) {
			throw new UserException('Impossible de supprimer un fichier car vous n\'avez pas le droit de le supprimer');
		}
	}

	unset($file);

	foreach ($check as $file) {
		$file->delete();
	}
}, $csrf_key, '!docs/?p=' . $parent);

$form->runIf(f('move') && f('select'), function () use ($check, $session) {
	foreach ($check as &$file) {
		$file = Files::get($file);

		if (!$file || !$file->checkWriteAccess($session) || $file->context() != File::CONTEXT_DOCUMENTS) {
			throw new UserException('Impossible de déplacer un fichier car vous n\'avez pas le droit de le modifier');
		}
	}

	$target = f('select');
	unset($file);

	foreach ($check as $file) {
		$file->move($target);
	}
}, $csrf_key, '!docs/?p=' . $parent);

$count = count($check);

$extra = compact('parent', 'action', 'check');
$tpl->assign(compact('csrf_key', 'extra', 'action', 'count'));

if ($action == 'delete') {
	$tpl->display('docs/action_delete.tpl');
}
else {
	$parent = f('current') ?? f('parent');

	if (!$parent) {
		$first_file = Files::get(current($check));

		if (!$first_file) {
			throw new UserException('Fichier introuvable');
		}

		$parent = $first_file->parent;
	}

	$directories = Files::list($parent);
	$directories = array_filter($directories, function (File $file) {
		return $file->type == File::TYPE_DIRECTORY;
	});

	$breadcrumbs = Files::getBreadcrumbs($parent);

	$tpl->assign(compact('directories', 'breadcrumbs', 'parent'));

	$tpl->display('docs/action_move.tpl');
}
