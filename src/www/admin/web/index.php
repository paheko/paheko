<?php

namespace Garradin;

use Garradin\Web\Web;
use Garradin\Entities\Web\Page;

require_once __DIR__ . '/_inc.php';

$parent = qg('parent') ?: null;
$cat = null;

if ($parent) {
	$cat = Web::getByURI($parent);

	if (!$cat) {
		throw new UserException('Catégorie inconnue');
	}
}

$order_date = qg('order_title') === null;

$categories = Web::listCategories($cat ? $cat->path : null);
$pages = Web::listPages($cat ? $cat->path : null, $order_date);
$title = $parent ? sprintf('Gestion du site web : %s', $cat->title) : 'Gestion du site web';
$type_page = Page::TYPE_PAGE;
$type_category = Page::TYPE_CATEGORY;
$breadcrumbs = $cat ? $cat->getBreadcrumbs() : [];

$tpl->assign(compact('categories', 'pages', 'title', 'parent', 'type_page', 'type_category', 'order_date', 'breadcrumbs'));

$tpl->display('web/index.tpl');
