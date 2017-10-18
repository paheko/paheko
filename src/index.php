<?php

// Ce fichier n'est pas censé être appelé sauf si l'installation de Garradin
// n'est pas effectuée correctement avec le vhost pointant sur le répertoire www/
// auquel cas on limite les dégâts

// Juste une vérification avant de continuer
if (!version_compare(phpversion(), '5.6.0', '>='))
{
	die('PHP 5.6.0 ou supérieur est nécessaire au fonctionnement de Garradin.');
}

header('Location: www/');
