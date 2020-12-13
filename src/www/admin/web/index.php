<?php

namespace Garradin;

use Garradin\Web;
use Garradin\Entities\Web\Page;

require_once __DIR__ . '/_inc.php';

$parent = (int) qg('parent') ?: null;

if ($parent) {
	$cat = Web::get($parent);

	if (!$cat) {
		throw new UserException('Catégorie inconnue');
	}
}

$order_date = qg('order_title') === null;

$categories = Web::listCategories($parent);
$pages = Web::listPages($parent, $order_date);
$title = $parent ? sprintf('Gestion du site web : %s', $cat->title) : 'Gestion du site web';
$type_page = Page::TYPE_PAGE;
$type_category = Page::TYPE_CATEGORY;

$tpl->assign(compact('categories', 'pages', 'title', 'parent', 'type_page', 'type_category', 'order_date'));

$tpl->display('web/index.tpl');
