<?php
namespace Garradin;

use Garradin\Web;
use Garradin\Entities\Web\Page;

require_once __DIR__ . '/_inc.php';

$parent = (int)qg('parent');
$breadcrumbs = [];

if ($parent) {
	$page = Web::get($parent);

	if (!$page) {
		throw new UserException('Page inconnue');
	}

	$tpl->assign('page', $page);
	$breadcrumbs = $page->getBreadcrumbs();
}

$tpl->assign(compact('breadcrumbs', 'parent'));

$tpl->assign('categories', Web::listCategories($parent));

$tpl->display('web/_selector.tpl');
