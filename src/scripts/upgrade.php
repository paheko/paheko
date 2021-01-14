<?php

namespace Garradin;

const UPGRADE_PROCESS = true;

require_once __DIR__ . '/../include/init.php';

$config = Config::getInstance();

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
