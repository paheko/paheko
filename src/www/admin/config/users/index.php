<?php
namespace Paheko;

use Paheko\Users\DynamicFields;
use Paheko\Users\Categories;
use Paheko\Users\Session;
use Paheko\Users\Users;

require_once __DIR__ . '/../_inc.php';

$csrf_key = 'users_config';
$df = DynamicFields::getInstance();
$config = Config::getInstance();

$form->runIf('save', function() use ($df, $config) {
	$config->importForm();
	$config->save();

	if (!empty($_POST['login_field'])) {
		$df->changeLoginField($_POST['login_field'], Session::getInstance());
	}

	if (!empty($_POST['name_fields'])) {
		$df->changeNameFields(array_keys($_POST['name_fields']));
	}

	if (isset($_POST['local_login']) && USER_CONFIG_FILE) {
		Install::setConfig(USER_CONFIG_FILE, 'LOCAL_LOGIN', (int)$_POST['local_login']);
	}
}, $csrf_key, Utils::getSelfURI(['ok' => 1]));

$names = $df->listAssocNames();
$name_fields = array_intersect_key($names, array_flip(DynamicFields::getNameFields()));

$first_admin_user = LOCAL_LOGIN !== null ? Users::getFirstAdmin() : null;

$tpl->assign([
	'has_parents'      => Users::hasParents(),
	'users_categories' => Categories::listAssoc(),
	'fields_list'      => $names,
	'login_field'      => DynamicFields::getLoginField(),
	'login_fields_list' => $df->listEligibleLoginFields(),
	'name_fields'      => $name_fields,
	'local_login'      => LOCAL_LOGIN,
	'can_configure_local_login' => Users::canConfigureLocalLogin(),
	'first_admin_user_name' => $first_admin_user ? $first_admin_user->name() : null,
	'log_retention_options' => [
		0 => 'Ne pas enregistrer de journaux',
		7 => 'Une semaine',
		30 => 'Un mois',
		90 => '3 mois',
		180 => '6 mois',
		365 => 'Un an',
		720 => 'Deux ans',
	],
	'logout_delay_options' => [
		0 => 'Pas de déconnexion automatique',
		1 => '1 minute',
		15 => '15 minutes',
		30 => '30 minutes',
		60 => '1 heure',
		2*60 => '2 heures',
		3*60 => '3 heures',
		6*60 => '6 heures',
	],
]);

$tpl->assign(compact('csrf_key', 'config'));

$tpl->display('config/users/index.tpl');
