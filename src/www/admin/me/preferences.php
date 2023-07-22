<?php
namespace Paheko;

use Paheko\Users\Session;

require_once __DIR__ . '/_inc.php';

$user = Session::getLoggedUser();

$ok = qg('ok');

$preferences = $user->preferences;
$csrf_key = 'my_preferences';

$form->runIf('save', function () use ($user) {
	foreach ($user::PREFERENCES as $key => $v) {
		$user->setPreference($key, f($key));
	}
	$user->save();
}, $csrf_key, '!me/preferences.php?ok');

$folders_options = [
	true => 'En galerie',
	false => 'En liste',
];

$page_size_options = [
	25 => 25,
	50 => 50,
	100 => 100,
	200 => 200,
	500 => 500,
];

$themes_options = [
	false => 'Thème clair',
	true => 'Thème sombre',
];

$handheld_options = [
	false => 'S\'adapter automatiquement à la taille de l\'écran',
	true => 'Toujours utiliser la disposition pour petit écran',
];

$tpl->assign(compact('preferences', 'ok', 'csrf_key', 'folders_options', 'page_size_options', 'themes_options', 'handheld_options'));

$tpl->display('me/preferences.tpl');
