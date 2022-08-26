<?php

namespace Garradin;

use Garradin\Email\Emails;

if (PHP_SAPI != 'cli') {
	die("Wrong call");
}

require_once __DIR__ . '/../include/init.php';

// Send messages in queue
Emails::runQueue();
