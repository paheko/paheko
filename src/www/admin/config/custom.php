<?php
namespace Paheko;

use Paheko\Users\Categories;
use Paheko\Files\Files;
use Paheko\Entities\Files\File;

require_once __DIR__ . '/_inc.php';

$config = Config::getInstance();

$form->runIf('save', function () use ($config) {
	$config->importForm();

	if (f('admin_background') == 'RESET') {
		$config->setFile('admin_background', null);
	}
	elseif (f('admin_background')) {
		$config->setFile('admin_background', base64_decode(f('admin_background')));
	}

	$config->save();
}, 'config_custom', Utils::getSelfURI(['ok' => '']));

$tpl->assign([
	'color1' => ADMIN_COLOR1,
	'color2' => ADMIN_COLOR2,
]);

$tpl->assign('background_image_current', $config->fileURL('admin_background'));
$tpl->assign('background_image_default', ADMIN_BACKGROUND_IMAGE);

$tpl->assign('custom_js', ['color_helper.js']);
$tpl->display('config/custom.tpl');
