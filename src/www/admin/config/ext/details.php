<?php
namespace Paheko;

use Paheko\Extensions;
use Paheko\Users\Session;

require_once __DIR__ . '/../_inc.php';

$type = qg('type');
$ext = Extensions::get($type, qg('name'));

if (!$ext) {
	throw new UserException('Extension inconnue');
}

$ext = Extensions::normalize($ext);
$csrf_key = 'ext_' . $ext->name;
$module = $ext->module ?? null;
$plugin = $ext->plugin ?? null;

$form->runIf(f('enable') || f('disable'), function () use ($ext) {
	$enabled = f('enable') ? true : false;
	Extensions::toggle($ext->type, $ext->name, $enabled);
	Utils::redirect(sprintf('!config/ext/details.php?type=%s&name=%s&toggle=%d', $ext->type, $ext->name, $enabled));
}, $csrf_key);

if (isset($_GET['disk'])) {
	$mode = 'disk';
}
elseif (isset($_GET['readme'])) {
	$mode = 'readme';
	$ext_object = $module ?? $plugin;
	$tpl->assign('content', $ext_object->fetchFile($ext_object::README_FILE));
	$tpl->assign('custom_css', ['config.css', '/content.css']);
}
else {
	$mode = 'details';

	$snippets = $module ? $module->listSnippets() : [];
	$access_details = [];

	if ($ext->config_url) {
		$access_details[] = sprintf('Cette extension a une <a href="%s">page de configuration</a>', $ext->config_url);
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
