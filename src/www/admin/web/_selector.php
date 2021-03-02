<?php
namespace Garradin;

use Garradin\Web\Web;
use Garradin\Entities\Web\Page;

require_once __DIR__ . '/_inc.php';

// Force dialog mode
$_GET['_dialog'] = true;

$parent = qg('parent') ?: null;
$breadcrumbs = [];

if ($parent) {
	$page = Web::getByURI($parent);

	if (!$page) {
		throw new UserException('Page inconnue');
	}

	$tpl->assign('page', $page);
	$breadcrumbs = $page->getBreadcrumbs();
}

$tpl->assign(compact('breadcrumbs', 'parent'));

$tpl->assign('categories', Web::listCategories($parent));

$tpl->display('web/_selector.tpl');
