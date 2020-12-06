<?php

namespace Garradin;

use Garradin\Services\Reminders;

require_once __DIR__ . '/../include/init.php';

// Exécution des tâches automatiques

if (ENABLE_AUTOMATIC_BACKUPS && $config->get('frequence_sauvegardes') && $config->get('nombre_sauvegardes'))
{
	$s = new Sauvegarde;
	$s->auto();
}

// Exécution des rappels automatiques
Reminders::sendPending();
