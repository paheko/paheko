<?php
namespace Garradin;

use Garradin\Accounting\Graph;

require_once __DIR__ . '/../_inc.php';

qv(['type' => 'string|required']);

header('Content-Type: image/svg+xml');

$expiry = time() - 600;
$hash = sha1('plot_all');

if (!Utils::HTTPCache($hash, $expiry)) {
	echo Graph::plot(qg('type'), [], Graph::MONTHLY_INTERVAL, 600);
}
