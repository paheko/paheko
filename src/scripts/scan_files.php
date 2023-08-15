<?php

namespace Paheko;

use Paheko\Files\Storage;

if (PHP_SAPI != 'cli' && !defined('\Paheko\ROOT')) {
	die("Wrong call");
}

require_once __DIR__ . '/../include/init.php';

Storage::sync();
