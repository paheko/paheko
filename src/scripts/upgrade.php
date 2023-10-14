<?php

namespace Paheko;

const INSTALL_PROCESS = true;

if (PHP_SAPI != 'cli') {
	die("Wrong call");
}

require_once __DIR__ . '/../include/init.php';

try {
	if (Upgrade::preCheck()) {
		Upgrade::upgrade();
		exit(2);
	}
	else {
		exit(0);
	}
}
catch (UserException $e) {
	echo $e->getMessage() . PHP_EOL;
	exit(1);
}
