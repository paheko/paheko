<?php
namespace Paheko;

require_once __DIR__ . '/../_inc.php';

$session->requireAccess($session::SECTION_ACCOUNTING, $session::ACCESS_ADMIN);

$form->runIf('save_config', function () {
	$config = Config::getInstance();
	$config->importForm(['analytical_set_all' => f('analytical_set_all')]);
	$config->save();
}, 'save_config', Utils::getSelfURI(['msg' => 'SAVED']));


$tpl->display('acc/projects/config.tpl');
