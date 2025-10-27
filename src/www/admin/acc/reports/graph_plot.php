<?php
namespace Paheko;

use Paheko\Accounting\Graph;

require_once __DIR__ . '/_inc.php';

header('Content-Type: image/svg+xml');

echo Graph::plot(qg('type'), $criterias);
