<?php
namespace Paheko;

use Paheko\Backup;

require_once __DIR__ . '/../_inc.php';

$csrf_key = 'backup_config';
$config = Config::getInstance();

$form->runIf('config', function () use ($config) {
	$source = $_POST;
	$option = $source['backup'] ?? 'none';

	if ($option === 'none') {
		$source['backup_frequency'] = 0;
	}
	elseif ($option === 'auto') {
		$source['backup_frequency'] = -1;
	}

	$config->importForm($source);
	$config->save();
}, $csrf_key, '!config/backup/auto.php?msg=CONFIG_SAVED');

$frequencies = Config::BACKUP_FREQUENCIES;

if ($config->backup_frequency === -1) {
	$backup = 'auto';
}
elseif (!$config->backup_frequency) {
	$backup = 'none';
}
else {
	$backup = 'custom';
}

$tpl->assign(compact('frequencies', 'backup', 'csrf_key'));

$tpl->display('config/backup/auto.tpl');
