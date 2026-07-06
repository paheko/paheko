<?php
namespace Paheko;

require_once __DIR__ . '/_inc.php';

$csrf_key = 'save_acc_config';

$form->runIf('save', function () {
	$config = Config::getInstance();
	$config->importForm([
		'analytical_set_all'   => $_POST['analytical_set_all'] ?? null,
		'analytical_mandatory' => boolval($_POST['analytical_mandatory'] ?? false),
	]);
	$config->save();
}, $csrf_key, Utils::getSelfURI(['msg' => 'SAVED']));


$tpl->assign(compact('csrf_key'));

$tpl->display('config/accounting.tpl');
