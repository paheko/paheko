<?php

namespace Garradin;

use Garradin\Users\Session;

use Garradin\Files\Files;
use Garradin\Entities\Files\File;

require_once __DIR__ . '/_inc.php';

$check = f('check');
$action = f('action');
$parent = f('parent');

$actions = ['move', 'delete', 'zip'];

if (!is_array($check) || !count($check) || !in_array($action, $actions)) {
	throw new UserException('Action invalide');
}

$csrf_key = 'action_' . $action;

$form->runIf('zip', function() use ($check, $session) {
	Files::zip(null, $check, $session);
	exit;
}, $csrf_key);

$form->runIf('confirm_delete', function () use ($check, $session) {
	foreach ($check as &$file) {
		$file = Files::get($file);

		if (!$file || !$file->canDelete()) {
			throw new UserException('Impossible de supprimer un fichier car vous n\'avez pas le droit de le supprimer');
		}
	}

	unset($file);

	foreach ($check as $file) {
		$file->delete();
	}
}, $csrf_key, '!docs/?path=' . $parent);

$form->runIf(f('move') && f('select'), function () use ($check, $session) {
	$target = f('select');

	foreach ($check as &$file) {
		$file = Files::get($file);

		if (!$file || !$file->canMove($target)) {
			throw new UserException('Impossible de déplacer un fichier car vous n\'avez pas le droit de le déplacer à cet endroit');
		}
	}

	unset($file);

	foreach ($check as $file) {
		$file->move($target);
	}
}, $csrf_key, '!docs/?path=' . $parent);

$count = count($check);

$extra = compact('parent', 'action', 'check');
$tpl->assign(compact('csrf_key', 'extra', 'action', 'count'));

if ($action == 'delete') {
	$tpl->display('docs/action_delete.tpl');
}
elseif ($action == 'zip') {
	$size = 0;

	foreach ($check as $selected) {
		foreach (Files::listRecursive($selected, Session::getInstance(), false) as $file) {
			$size += $file->size;
		}
	}

	$tpl->assign(compact('extra', 'count', 'size'));
	$tpl->display('docs/action_zip.tpl');
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
