<?php
namespace Paheko;

use Paheko\Backup;

require_once __DIR__ . '/../_inc.php';

$csrf_key = 'backup_config';

$form->runIf('config', function () {
	$frequency = (int) f('backup_frequency');

	if ($frequency < 0 || $frequency > 365) {
		throw new UserException('Fréquence invalide');
	}

	$number = (int) f('backup_limit');

	if ($number < 0 || $number > 50) {
		throw new UserException('Nombre de sauvegardes invalide. Le maximum est de 50 sauvegardes.');
	}

	$config = Config::getInstance();
	$config->set('backup_frequency', $frequency);
	$config->set('backup_limit', $number);
	$config->save();
}, $csrf_key, '!config/backup/?msg=CONFIG_SAVED');

$frequencies = [
	0 => 'Aucun — les sauvegardes automatiques sont désactivées',
	1 => 'Quotidienne, tous les jours',
	7 => 'Hebdomadaire, tous les 7 jours',
	15 => 'Bimensuelle, tous les 15 jours',
	30 => 'Mensuelle',
	90 => 'Trimestrielle',
	365 => 'Annuelle',
];

$tpl->assign(compact('frequencies', 'csrf_key'));

$tpl->display('config/backup/config.tpl');
