<?php
namespace Paheko;

require_once __DIR__ . '/_inc.php';

$csrf_key = 'save_web_config';

$form->runIf('save', function () {
	$config = Config::getInstance();
	if (isset($_POST['site_disabled'])) {
		$config->set('site_disabled', boolval($_POST['site_disabled']));
	}
	$config->importForm(['org_web' => $_POST['org_web'] ?? null]);
	$config->save();
}, $csrf_key, Utils::getSelfURI(['msg' => 'SAVED']));


$tpl->assign(compact('csrf_key'));

$tpl->display('config/web.tpl');
