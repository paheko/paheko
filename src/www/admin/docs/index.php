<?php

namespace Paheko;

use Paheko\Files\Files;
use Paheko\Files\Transactions;
use Paheko\Files\Users as Users_Files;
use Paheko\Files\Trash;
use Paheko\Users\Users;
use Paheko\Users\Session;
use Paheko\Entities\Files\File;

require_once __DIR__ . '/../_inc.php';

$highlight = null;

if ($id = qg('id')) {
	$dir = Files::getByHashID($id);
}
else {
	if (qg('f')) {
		$pos = strrpos(qg('f'), '/');
		$path = substr(qg('f'), 0, $pos);
		$highlight = substr(qg('f'), $pos + 1);
	}
	else {
		$path = qg('path') ?: File::CONTEXT_DOCUMENTS;
	}

	$dir = Files::get($path);
}

if (!$dir || !$dir->isDir()) {
	throw new UserException('Ce répertoire n\'existe pas.');
}

if (!$dir->canRead()) {
	throw new UserException('Vous n\'avez pas accès à ce répertoire');
}

$path = $dir->path;

$context = Files::getContext($path);
$context_ref = Files::getContextRef($path);
$list = null;
$user_name = null;
$context_specific_root = false;

// Specific lists for some contexts
if ($context == File::CONTEXT_TRANSACTION || $context == File::CONTEXT_USER) {
	if (!$context_ref) {
		$context_specific_root = true;

		if ($context == File::CONTEXT_TRANSACTION) {
			$list = Transactions::list();
		}
		elseif ($context == File::CONTEXT_USER) {
			$list = Users_Files::list();
		}
	}
	elseif ($context_ref && $context == File::CONTEXT_USER) {
		$user_name = Users::getName($context_ref);
	}
}
else {
	$context_ref = null;
}

if (null === $list) {
	$list = Files::getDynamicList($path);
}

$list->loadFromQueryString();

$breadcrumbs = Files::getBreadcrumbs($path);

$pref = Session::getPreference('folders_gallery');
$gallery = $pref ?? true;

if (null !== qg('gallery')) {
	$gallery = (bool) qg('gallery');
}

if ($gallery !== $pref) {
	Session::getLoggedUser()->setPreference('folders_gallery', $gallery);
}

$parent_uri = $dir->parent_uri();
$dir_uri = $dir->uri();

$quota = [
	'used' => Files::getUsedQuota(),
	'max' => Files::getQuota(),
];

$quota['left'] = Files::getRemainingQuota($quota['used']);

foreach ($quota as $key => $value) {
	$quota[$key . '_bytes'] = Utils::format_bytes($value);
}

$quota['percent'] = $quota['max'] ? round(($quota['used'] / $quota['max']) * 100) : 100;

if ($context === File::CONTEXT_TRANSACTION) {
	if ($context_ref) {
		$title = sprintf('Écriture #%s', $context_ref);
	}
	else {
		$title = 'Fichiers joints aux écritures comptables';
	}
}
elseif ($context == File::CONTEXT_USER) {
	if ($context_ref) {
		$title = sprintf('Fichiers joints à la fiche du membre : %s', $user_name);
	}
	else {
		$title = 'Fichiers joints aux fiches des membres';
	}
}
elseif ($parent_uri) {
	$title = sprintf('%s — Documents', $dir->name);
}
else {
	$title = 'Documents';
}

$tpl->assign(compact('list', 'parent_uri', 'dir_uri', 'dir', 'context', 'context_ref',
	'breadcrumbs', 'highlight', 'user_name', 'gallery', 'context_specific_root',
	'quota', 'title'));

$tpl->display('docs/index.tpl');
