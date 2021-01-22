<?php
namespace Garradin;

require_once __DIR__ . '/_inc.php';

$config = Config::getInstance();

$form->runIf('save', function () use ($config) {
	$config->importForm();
	$config->save();
}, 'config', Utils::getSelfURI('ok'));

$server_time = time();

$tpl->assign('garradin_version', garradin_version() . ' [' . (garradin_manifest() ?: 'release') . ']');

$tpl->assign('new_version', ENABLE_TECH_DETAILS ? Utils::getLatestVersion() : null);
$tpl->assign('php_version', phpversion());
$tpl->assign('has_gpg_support', \KD2\Security::canUseEncryption());
$tpl->assign('server_time', $server_time);

$v = \SQLite3::version();
$tpl->assign('sqlite_version', $v['versionString']);

$tpl->assign('countries', Utils::getCountryList());

$cats = new Membres\Categories;
$tpl->assign('membres_cats', $cats->listSimple());

$tpl->assign('champs', $config->get('champs_membres')->getList());

$tpl->assign('couleur1', $config->get('couleur1') ?: ADMIN_COLOR1);
$tpl->assign('couleur2', $config->get('couleur2') ?: ADMIN_COLOR2);

$image_fond = $config->get('image_fond') ? $config->get('image_fond')->url() : ADMIN_BACKGROUND_IMAGE;

$tpl->assign('background_image_source', $image_fond);
$tpl->assign('background_image_current', f('image_fond') ?: $image_fond);
$tpl->assign('background_image_default', ADMIN_BACKGROUND_IMAGE);

$tpl->assign('custom_js', ['color_helper.js']);
$tpl->display('admin/config/index.tpl');
