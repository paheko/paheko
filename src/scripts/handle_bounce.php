<?php

namespace Paheko;

use Paheko\Email\Emails;

require_once __DIR__ . '/../include/init.php';

if (PHP_SAPI != 'cli') {
	echo "This command can only be called from the command-line.\n";
	exit(1);
}

$message = file_get_contents('php://stdin');

if (empty($message)) {
	echo "No STDIN content was provided.\nPlease provide the email message on STDIN.\n";
	exit(2);
}

Emails::handleBounce($message);
