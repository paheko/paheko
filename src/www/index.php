<?php

namespace Garradin;

use Garradin\Web\Web;

require __DIR__ . '/_inc.php';

// Handle __un__subscribe URL
if (!empty($_GET['un']) && ctype_alnum($_GET['un'])) {
	Utils::redirect('!optout.php?code=' . $_GET['un']);
}

Web::dispatchURI();
