<?php
namespace Paheko;

use Paheko\Extensions;
use Paheko\Users\Session;

require_once __DIR__ . '/../_inc.php';

$ext = Extensions::get(qg('name'));

if (!$ext) {
	throw new UserException('Extension inconnue');
}

$csrf_key = 'ext_' . $ext->name;
$module = $ext->module ?? null;
$plugin = $ext->plugin ?? null;

if ($ext->broken_message) {
	throw new UserException($ext->broken_message);
}

$form->runIf('enable', function () use ($ext) {
	$ext->enable();
}, $csrf_key, $ext->details_url);

$form->runIf('disable', function () use ($ext) {
	$ext->disable();
}, $csrf_key, $ext->details_url);

if (isset($_GET['disk'])) {
	$mode = 'disk';
}
else {
	$mode = 'details';

	$snippets = $ext->listSnippets();
	$access_details = [];

	if ($ext->config_url) {
		$access_details[] = sprintf('Cette extension a une <a href="%s">page de configuration</a>.', $ext->config_url)
			. '<br /><em>(Seuls les administrateurs ayant accès à la configuration générale pourront accéder à cette page de configuration.)</em>';
	}

	if (!empty($ext->menu)) {
		$access_details[] = 'Cette extension ajoute un élément au menu de gauche, en dessous de la page d\'accueil.';
	}

	if (!empty($ext->home_button)) {
		$access_details[] = 'Cette extension ajoute un bouton sur la page d\'accueil.';
	}

	foreach ($snippets as $label) {
		$access_details[] = sprintf('Cette extension insère un élément&nbsp;: <strong>%s</strong>', htmlspecialchars($label));
	}

	$tpl->assign(compact('access_details'));
}

$tpl->assign('url_help_plugins', 'https://fossil.kd2.org/paheko/wiki/?name=Extensions');

$tpl->assign(compact('mode', 'csrf_key', 'ext', 'module', 'plugin'));

$tpl->display('config/ext/details.tpl');
