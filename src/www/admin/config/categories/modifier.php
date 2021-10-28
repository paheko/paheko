<?php
namespace Garradin;

use Garradin\Entities\Users\Category;
use Garradin\Users\Categories;
use Garradin\Membres\Session;

require_once __DIR__ . '/../_inc.php';

$cat = Categories::get((int) qg('id'));

if (!$cat) {
	throw new UserException("Cette catégorie n'existe pas.");
}

$user = $session->getUser();

$csrf_key = 'cat_edit_' . $cat->id();

$form->runIf('save', function () use ($cat, $session) {
	$user = $session->getUser();
	$cat->importForm();
	$cat->hidden = (int) f('hidden');

	// Ne pas permettre de modifier la connexion, l'accès à la config et à la gestion des membres
	// pour la catégorie du membre qui édite les catégories, sinon il pourrait s'empêcher
	// de se connecter ou n'avoir aucune catégorie avec le droit de modifier les catégories !
	if ($cat->id() == $user->id_category) {
		$cat->set('perm_connect', Session::ACCESS_READ);
		$cat->set('perm_config', Session::ACCESS_ADMIN);
	}

	$cat->save();

	if ($cat->id() == $user->id_category) {
		$session->refresh();
	}
}, $csrf_key, '!config/categories/');


$permissions = Category::PERMISSIONS;

foreach ($permissions as $key => &$config) {
	if ($cat->id() == $user->id_category && in_array($key, [Session::SECTION_CONFIG, Session::SECTION_CONNECT])) {
		$config['disabled'] = true;
	}
}

unset($config);

$tpl->assign(compact('csrf_key', 'cat', 'permissions'));

$tpl->display('admin/config/categories/modifier.tpl');
