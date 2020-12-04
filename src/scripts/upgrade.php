<?php

namespace Garradin;

const UPGRADE_PROCESS = true;

require_once __DIR__ . '/../include/init.php';

$config = Config::getInstance();

if (version_compare($config->getVersion(), garradin_version(), '<')) {
	try {
		if (Upgrade::preCheck()) {
			Upgrade::upgrade();
		}
	}
	catch (UserException $e) {
		echo $e->getMessage() . PHP_EOL;
		exit(1);
	}

	exit(2);
}

exit(0);
