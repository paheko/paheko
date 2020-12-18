<?php

namespace Garradin;

use Garradin\Web;
use Garradin\Entities\Web\Page;

require_once __DIR__ . '/_inc.php';

if ($uri = qg('uri'))
{
	$page_uri = Wiki::transformTitleToURI($uri);
	$page = Web::getByURI($page_uri);
}
else
{
	$page = Web::get((int) qg('id'));
}

if (!$page) {
	throw new UserException('Page inconnue');
}

$membres = new Membres;

$tpl->assign('breadcrumbs', $page->getBreadcrumbs());
$tpl->assign('auteur', $page->file()->author_id ? $membres->getNom($page->file()->author_id) : null);

$images = $page->getImageGallery(false);
$files = $page->getAttachmentsGallery(false);

$content = $page->render(['prefix' => ADMIN_URL . 'web/page.php?uri=']);

$type_page = Page::TYPE_PAGE;
$type_category = Page::TYPE_CATEGORY;

$tpl->assign(compact('page', 'images', 'files', 'content', 'type_page', 'type_category'));

$tpl->assign('custom_js', ['wiki_gallery.js']);

$tpl->display('web/page.tpl');
