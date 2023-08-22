<?php
namespace Paheko;

use Paheko\Web\Web;
use Paheko\Entities\Web\Page;

require_once __DIR__ . '/_inc.php';

// Force dialog mode
$_GET['_dialog'] = true;

$current = null;

if (f('current')) {
	$current = Web::getById((int)f('current'));
}
elseif (null === f('current') && qg('path')) {
	$current = Web::get(qg('path'));
}

if ($current) {
	$parent_id = $current->parent ? Web::get($current->parent)->id() : null;
	$current_cat_id = $current->id();
	$current_cat_path = $current->path();
	$current_cat_title = $current->title;
	$breadcrumbs = $current->getBreadcrumbs();
}
else {
	$parent_id = null;
	$current_cat_id = null;
	$current_cat_path = '';
	$current_cat_title = 'Racine du site';
	$breadcrumbs = [];
}

$breadcrumbs = [null => 'Racine du site'] + $breadcrumbs;
$categories = Web::listCategories($current ? $current->path : null);

// used to avoid being able to put a category inside itself
$id_page = (int) qg('id_page');

$tpl->assign(compact('breadcrumbs', 'parent_id', 'categories', 'current_cat_id', 'current_cat_title', 'current_cat_path', 'id_page'));

$tpl->display('web/_selector.tpl');
