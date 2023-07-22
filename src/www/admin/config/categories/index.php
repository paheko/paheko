<?php
namespace Paheko;

use Paheko\Entities\Users\Category;
use Paheko\Users\Categories;
use Paheko\Users\Session;

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

$tpl->display('config/categories/index.tpl');
