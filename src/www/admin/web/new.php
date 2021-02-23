<?php

namespace Garradin;

use Garradin\Web\Web;
use Garradin\Entities\Web\Page;
use Garradin\Entities\Files\File;
use KD2\SimpleDiff;

require_once __DIR__ . '/_inc.php';

$csrf_key = 'web_page_new';

$form->runIf('create', function () {
	$page = Page::create((int) qg('type'), qg('parent') ?: null, f('title'), Page::STATUS_DRAFT);
	$page->save();
	Utils::redirect(ADMIN_URL . 'web/edit.php?new&id=' . $page->id());
}, $csrf_key);

$title = qg('type') == Page::TYPE_CATEGORY ? 'Nouvelle catégorie' : 'Nouvelle page';

$tpl->assign(compact('title', 'csrf_key'));

$tpl->display('web/new.tpl');
