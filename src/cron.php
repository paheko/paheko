<?php

namespace Garradin;

require_once __DIR__ . '/include/init.php';

// Exécution des tâches automatiques

if (ENABLE_AUTOMATIC_BACKUPS && $config->get('frequence_sauvegardes') && $config->get('nombre_sauvegardes'))
{
	$s = new Sauvegarde;
	$s->auto();
}

// Exécution des rappels automatiques
$rappels = new Rappels;

if ($rappels->countAll())
{
	$rappels->sendPending();
}
