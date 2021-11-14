<?php

namespace Garradin;

use Garradin\Web\Web;

require __DIR__ . '/_inc.php';

// Handle __un__subscribe URL
if (!empty($_GET['un'])) {
	$params = array_intersect_key($_GET, ['un' => null, 'v' => null]);
	Utils::redirect('!optout.php?' . http_build_query($params));
}

Web::dispatchURI();
