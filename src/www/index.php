<?php

namespace Garradin;

use Garradin\Web;

require __DIR__ . '/_inc.php';

if (Config::getInstance()->get('desactiver_site'))
{
	Utils::redirect(ADMIN_URL);
}

Web::dispatchURI();
