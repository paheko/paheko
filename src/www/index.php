<?php

namespace Garradin;

require __DIR__ . '/_inc.php';

if (Config::getInstance()->get('desactiver_site'))
{
	Utils::redirect(ADMIN_URL);
}

$squelette = new Squelette;
$squelette->dispatchURI();
