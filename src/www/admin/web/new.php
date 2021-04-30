<?php

namespace Garradin;

use Garradin\Web\Web;
use Garradin\Entities\Web\Page;
use Garradin\Entities\Files\File;
use KD2\SimpleDiff;

require_once __DIR__ . '/_inc.php';

$session->requireAccess($session::SECTION_WEB, $session::ACCESS_WRITE);

$csrf_key = 'web_page_new';

$parent = qg('parent') ?: null;

$form->runIf('create', function () use ($parent) {
	$page = Page::create((int) qg('type'), $parent, f('title'), Page::STATUS_DRAFT);
	$page->save();

	$url = ADMIN_URL . 'web/edit.php?new&p=' . $page->path;

	if (null !== qg('_dialog')) {
		Utils::reloadParentFrame($url);
	}
	else {
		Utils::redirect($url);
	}
}, $csrf_key);

$title = qg('type') == Page::TYPE_CATEGORY ? 'Nouvelle catégorie' : 'Nouvelle page';

$tpl->assign(compact('title', 'csrf_key'));

$tpl->display('web/new.tpl');
