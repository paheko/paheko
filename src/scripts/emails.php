<?php

namespace Garradin;

use Garradin\Users\Emails;

if (PHP_SAPI != 'cli') {
	die("Wrong call");
}

require_once __DIR__ . '/../include/init.php';

// Send messages in queue
$count = Emails::runQueue();

if ($count && !empty($_SERVER['argv'][1]) && $_SERVER['argv'][1] == '-v') {
	printf("%d messages sent\n", $count);
}
