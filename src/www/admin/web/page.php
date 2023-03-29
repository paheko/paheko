<?php

namespace Garradin;

use Garradin\Web\Web;
use Garradin\Entities\Web\Page;

require_once __DIR__ . '/_inc.php';

$page = null;

if (qg('p')) {
	$page = Web::get(qg('p'));
}
elseif (qg('uri')) {
	$page = Web::getByURI(qg('uri'));
}

if (!$page) {
	throw new UserException('Page inconnue');
}

if (!$page) {
	throw new UserException('Page inconnue');
}

if (qg('toggle_type') !== null && $session->canAccess($session::SECTION_WEB, $session::ACCESS_ADMIN)) {
	$page->toggleType();
	$page->save();
	Utils::redirect('!web/page.php?p=' . $page->path);
}

$tpl->assign('breadcrumbs', $page->getBreadcrumbs());

$images = $page->getImageGallery(true);
$files = $page->getAttachmentsGallery(true);

$content = $page->render(ADMIN_URL . 'web/page.php?uri=');

$type_page = Page::TYPE_PAGE;
$type_category = Page::TYPE_CATEGORY;
$links_errors = $page->checkInternalLinks();

$tpl->assign(compact('page', 'images', 'files', 'content', 'type_page', 'type_category', 'links_errors'));

$tpl->assign('custom_js', ['web_gallery.js']);
$tpl->assign('custom_css', ['web.css', '!web/css.php']);

$tpl->display('web/page.tpl');
