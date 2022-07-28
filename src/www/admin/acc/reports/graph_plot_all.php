<?php
namespace Garradin;

use Garradin\Accounting\Graph;

require_once __DIR__ . '/../_inc.php';

qv(['type' => 'string|required']);

header('Content-Type: image/svg+xml');

$expiry = time() - 30;
$hash = sha1('graph_plot_all');

if (!Utils::HTTPCache($hash, $expiry)) {
	echo Graph::bar(qg('type'), []);
}
