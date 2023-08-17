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

if (qg('f')) {
	$pos = strrpos(qg('f'), '/');
	$path = substr(qg('f'), 0, $pos);
	$highlight = substr(qg('f'), $pos + 1);
}
else {
	$path = qg('path') ?: File::CONTEXT_DOCUMENTS;
}

$dir = Files::get($path);

if (!$dir || !$dir->isDir()) {
	throw new UserException('Ce répertoire n\'existe pas.');
}

if (!$dir->canRead()) {
	throw new UserException('Vous n\'avez pas accès à ce répertoire');
}

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

$quota_used = Files::getUsedQuota();
$quota_max = Files::getQuota();
$quota_left = Files::getRemainingQuota($quota_used);
$quota_percent = $quota_max ? round(($quota_used / $quota_max) * 100) : 100;

$pref = Session::getPreference('folders_gallery');
$gallery = $pref ?? true;

if (null !== qg('gallery')) {
	$gallery = (bool) qg('gallery');
}

if ($gallery !== $pref) {
	Session::getLoggedUser()->setPreference('folders_gallery', $gallery);
}

$dir_uri = $dir->path_uri();
$parent_uri = $dir->parent_uri();

$tpl->assign(compact('list', 'dir_uri', 'parent_uri', 'dir', 'context', 'context_ref',
	'breadcrumbs', 'quota_used', 'quota_max', 'quota_percent', 'quota_left',
	'highlight', 'user_name', 'gallery', 'context_specific_root'));

$tpl->display('docs/index.tpl');
