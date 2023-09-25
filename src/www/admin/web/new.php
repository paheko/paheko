<?php

namespace Paheko;

use Paheko\Web\Web;
use Paheko\Entities\Web\Page;
use Paheko\Entities\Files\File;
use KD2\SimpleDiff;

require_once __DIR__ . '/_inc.php';

$session->requireAccess($session::SECTION_WEB, $session::ACCESS_WRITE);

$csrf_key = 'web_page_new';

$parent = qg('parent') ?: '';

$form->runIf('create', function () use ($parent) {
	$type = (int) qg('type');
	$page = Page::create($type, $parent ?: null, f('title'), $type === Page::TYPE_PAGE ? Page::STATUS_DRAFT : Page::STATUS_ONLINE);
	$page->save();

	if ($page->type === Page::TYPE_CATEGORY) {
		$url = '!web/?id=' . $page->id();
	}
	else {
		$url = '!web/edit.php?new&id=' . $page->id();
	}

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
