<?php
namespace Garradin;

use Garradin\Web\Web;
use Garradin\Entities\Web\Page;

require_once __DIR__ . '/_inc.php';

// Force dialog mode
$_GET['_dialog'] = true;

$current = qg('current') ?? '';
$parent = qg('parent') ?? '';

$breadcrumbs = [];

if ($parent) {
	$page = Web::get($parent);

	if (!$page) {
		throw new UserException('Page inconnue');
	}

	$tpl->assign('page', $page);
	$breadcrumbs = $page->getBreadcrumbs();
}

$categories = Web::listCategories($parent);

$categories = array_filter($categories, function ($cat) use ($current) {
	return ($cat->path == $current) ? false : true;
});

$tpl->assign('selected', $current);
$tpl->assign(compact('breadcrumbs', 'parent', 'categories'));

$tpl->display('web/_selector.tpl');
