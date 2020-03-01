<?php

namespace Garradin;

if (PHP_VERSION_ID < 70200) {
	die("PHP 7.2 ou supérieur est requis.");
}

require __DIR__ . '/_inc.php';

if (Config::getInstance()->get('desactiver_site'))
{
	Utils::redirect(ADMIN_URL);
}

$squelette = new Squelette;
$squelette->dispatchURI();
