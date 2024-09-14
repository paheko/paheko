<?php

namespace Paheko;

use Paheko\Files\Conversion;

require_once __DIR__ . '/../../include/init.php';

Conversion::serveFromCache($_GET['i'] ?? '', $_GET['t'] ?? '');
