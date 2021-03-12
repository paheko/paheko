<?php
namespace Garradin;

use Garradin\Users\Categories;

require_once __DIR__ . '/_inc.php';

$config = Config::getInstance();

$form->runIf('save', function () use ($config) {
	$config->importForm();
	$config->save();
}, 'config', Utils::getSelfURI(['ok' => '']));

$tpl->assign([
	'garradin_version' => garradin_version() . ' [' . (garradin_manifest() ?: 'release') . ']',
	'new_version'      => ENABLE_TECH_DETAILS ? Utils::getLatestVersion() : null,
	'php_version'      => phpversion(),
	'has_gpg_support'  => \KD2\Security::canUseEncryption(),
	'server_time'      => time(),
	'sqlite_version'   => \SQLite3::version()['versionString'],
	'countries'        => Utils::getCountryList(),
	'membres_cats'     => Categories::listSimple(),
	'champs'           => $config->get('champs_membres')->listAssocNames(),
	'color1'           => ADMIN_COLOR1,
	'color2'           => ADMIN_COLOR2,
	'garradin_website' => WEBSITE,
]);

$admin_background = $config->get('admin_background');

if ($admin_background) {
	$admin_background = $config->get('admin_background')->url();
}

$tpl->assign('background_image_current', $admin_background);
$tpl->assign('background_image_default', ADMIN_BACKGROUND_IMAGE);

$tpl->assign('custom_js', ['color_helper.js']);
$tpl->display('admin/config/index.tpl');
