<?php

namespace Garradin;

use Garradin\Services\Reminders;
use Garradin\Files\Trash;

if (PHP_SAPI != 'cli' && !defined('\Garradin\ROOT')) {
	die("Wrong call");
}

require_once __DIR__ . '/../include/init.php';

// Exécution des tâches automatiques

$config = Config::getInstance();

if ($config->backup_frequency && $config->backup_limit) {
	Backup::auto();
}

// Send pending reminders
Reminders::sendPending();

// Make sure we are cleaning the trash
Trash::clean();

Plugins::fireSignal('cron');
