<?php
namespace Paheko;

use Paheko\Entities\Users\Category;
use Paheko\Users\Categories;
use Paheko\Users\Session;

use KD2\Security;

require_once __DIR__ . '/../_inc.php';

$cat = Categories::get((int) qg('id'));

if (!$cat) {
	throw new UserException("Cette catégorie n'existe pas.");
}

$user = $session->getUser();

$csrf_key = 'cat_edit_' . $cat->id();
$admin_safe = $session->isAdmin() && $cat->id == $user->id_category;

$form->runIf('save', function () use ($cat, $session) {
	$user = $session->getUser();
	$cat->importForm();

	// Ne pas permettre de modifier la connexion, l'accès à la config et à la gestion des membres
	// pour la catégorie du membre qui édite les catégories, sinon il pourrait s'empêcher
	// de se connecter ou n'avoir aucune catégorie avec le droit de modifier les catégories !
	if ($cat->id() === $user->id_category) {
		// Require force_otp to already have set up OTP, or you might not understand what you are doing
		if ($cat->isModified('force_otp') && $cat->force_otp) {
			if (!$user->otp_secret) {
				throw new UserException('Vous devez déjà activer la double authentification pour votre compte avant de pouvoir l\'obliger à la connexion.');
			}
			elseif (!$user->otp_recovery_codes) {
				throw new UserException('Vous devez déjà avoir des codes de secours configurés dans votre compte avant de pouvoir obliger la double authentification à la connexion.');
			}
		}

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
	if ($admin_safe && in_array($key, [Session::SECTION_CONFIG, Session::SECTION_CONNECT])) {
		$config['disabled'] = true;
	}
}

unset($config);

$has_encryption = Security::canUseEncryption();
$tpl->assign(compact('csrf_key', 'cat', 'permissions', 'has_encryption'));

$tpl->display('config/categories/edit.tpl');
