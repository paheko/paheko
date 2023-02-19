<?php

namespace Garradin;

use Garradin\Services\Reminders;

if (PHP_SAPI != 'cli' && !defined('\Garradin\ROOT')) {
	die("Wrong call");
}

require_once __DIR__ . '/../include/init.php';

// Exécution des tâches automatiques

$config = Config::getInstance();

if ($config->backup_frequency && $config->backup_limit)
{
	$s = new Sauvegarde;
	$s->auto();
}

// Exécution des rappels automatiques
Reminders::sendPending();

Plugins::fireSignal('cron');
