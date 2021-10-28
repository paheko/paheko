<?php
namespace Garradin;

use Garradin\Entities\Users\Category;
use Garradin\Users\Categories;
use Garradin\Membres\Session;

require_once __DIR__ . '/../_inc.php';

$csrf_key = 'cat_create';

$form->runIf('save', function() {
	$cat = new Category;

	$cat->importForm([
		'name'   => f('name'),
		'hidden' => 0,
	]);
	$cat->setAllPermissions(Session::ACCESS_NONE);

	$cat->save();
}, $csrf_key, Utils::getSelfURI());

$list =  Categories::listWithStats();

$tpl->assign(compact('list', 'csrf_key'));

$tpl->display('admin/config/categories/index.tpl');
