<?php

namespace Garradin;

use Garradin\Files\Files;
use Garradin\Files\Transactions;
use Garradin\Files\Users as Users_Files;
use Garradin\Users\Users;
use Garradin\Users\Session;
use Garradin\Entities\Files\File;

require_once __DIR__ . '/_inc.php';

$path = qg('path') ?: File::CONTEXT_DOCUMENTS;

$parent = Files::get($path);

if (!$parent || !$parent->isDir()) {
	throw new UserException('Ce répertoire n\'existe pas.');
}

if (!$parent->canRead()) {
	throw new UserException('Vous n\'avez pas accès à ce répertoire');
}

$context = Files::getContext($path);
$context_ref = Files::getContextRef($path);
$list = null;
$user_name = null;

// Specific lists for some contexts
if (!$context_ref) {
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

if (null == $list) {
	$list = Files::list($path);
}
elseif ($list instanceof DynamicList) {
	$list->loadFromQueryString();
}

$breadcrumbs = Files::getBreadcrumbs($path);

$parent_path = Utils::dirname($path);

$quota_used = Files::getUsedQuota();
$quota_max = Files::getQuota();
$quota_left = Files::getRemainingQuota();
$quota_percent = $quota_max ? round(($quota_used / $quota_max) * 100) : 100;

$pref = Session::getPreference('folders_gallery');
$gallery = $pref ?? false;

if (null !== qg('gallery')) {
	$gallery = (bool) qg('gallery');
}

if ($gallery !== $pref) {
	Session::getLoggedUser()->setPreference('folders_gallery', $gallery);
}

$tpl->assign(compact('path', 'list', 'parent', 'context', 'context_ref', 'breadcrumbs', 'parent_path', 'quota_used', 'quota_max', 'quota_percent', 'quota_left', 'user_name', 'gallery'));

$tpl->display('docs/index.tpl');
