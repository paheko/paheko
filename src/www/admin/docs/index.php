<?php

namespace Garradin;

use Garradin\Files\Files;
use Garradin\Entities\Files\File;

require_once __DIR__ . '/_inc.php';

$path = trim(qg('p')) ?: File::CONTEXT_DOCUMENTS;

$files = Files::list($path);

// We consider that the first file has the same rights as the others
if (count($files)) {
	$first = current($files);

	if (!$first->checkReadAccess($session)) {
		throw new UserException('Vous n\'avez pas accès à ce répertoire');
	}

	$can_delete = $first->checkDeleteAccess($session);
	$can_write = $first->checkWriteAccess($session);
}
else {
	$can_delete = $can_write = false;
}

$context = Files::getContext($path);
$context_ref = Files::getContextRef($path);

$can_create = File::checkCreateAccess($path, $session);
$can_upload = $can_create && (($context == File::CONTEXT_DOCUMENTS || $context == File::CONTEXT_SKELETON)
	|| (($context == File::CONTEXT_USER || $context == File::CONTEXT_TRANSACTION) && $context_ref));
$can_mkdir = $can_create && ($context == File::CONTEXT_DOCUMENTS || $context == File::CONTEXT_SKELETON);

$breadcrumbs = Files::getBreadcrumbs($path);

$parent_path = Utils::dirname($path);

$quota_used = Files::getUsedQuota();
$quota_max = Files::getQuota();
$quota_left = Files::getRemainingQuota();
$quota_percent = $quota_max ? round(($quota_used / $quota_max) * 100) : 100;

$tpl->assign(compact('path', 'files', 'can_write', 'can_delete', 'can_mkdir', 'can_upload', 'context', 'context_ref', 'breadcrumbs', 'parent_path', 'quota_used', 'quota_max', 'quota_percent', 'quota_left'));

$tpl->display('docs/index.tpl');
