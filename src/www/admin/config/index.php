<?php
namespace Garradin;

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
	'membres_cats'     => (new Membres\Categories)->listSimple(),
	'champs'           => $config->get('champs_membres')->listAssocNames(),
	'color1'           => ADMIN_COLOR1,
	'color2'           => ADMIN_COLOR2,
]);

$image_fond = $config->get('image_fond') ? $config->get('image_fond')->url() : null;

$tpl->assign('background_image_current', $image_fond);
$tpl->assign('background_image_default', ADMIN_BACKGROUND_IMAGE);

$tpl->assign('custom_js', ['color_helper.js']);
$tpl->display('admin/config/index.tpl');
