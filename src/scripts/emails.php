<?php

namespace Paheko;

use Paheko\Email\Emails;

if (PHP_SAPI != 'cli') {
	die("Wrong call");
}

require_once __DIR__ . '/../include/init.php';

$quiet = !empty($_SERVER['argv'][1]) && $_SERVER['argv'][1] == '-q';

// Send messages in queue
$sent = Emails::runQueue();

$count = Emails::countQueue();

if (!$quiet) {
	if ($sent) {
		printf("%d messages sent\n", $sent);
	}

	if ($count) {
		printf("%d messages still in queue\n", $count);
	}
}

if ($count) {
	exit(2);
}

exit(0);
