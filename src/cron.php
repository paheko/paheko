<?php

namespace Garradin;

require __DIR__ . '/include/init.php';

// Exécution des tâches automatiques

if ($config->get('frequence_sauvegardes') && $config->get('nombre_sauvegardes'))
{
	$s = new Sauvegarde;
	$s->auto();
}
