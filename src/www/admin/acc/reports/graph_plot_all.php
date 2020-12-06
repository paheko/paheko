<?php
namespace Garradin;

use Garradin\Accounting\Graph;

require_once __DIR__ . '/../_inc.php';

qv(['type' => 'string|required']);

header('Content-Type: image/svg+xml');

echo Graph::plot(qg('type'), [], Graph::MONTHLY_INTERVAL, 600);
