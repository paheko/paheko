<?php
namespace Paheko;

use Paheko\Accounting\Graph;

require_once __DIR__ . '/../_inc.php';

$type = $_GET['type'] ?? null;

if (!$type) {
	throw new UserException('Missing type');
}

header('Content-Type: image/svg+xml');

$expiry = time() - 30;
$hash = sha1('graph_plot_all');

if (!Utils::HTTPCache($hash, $expiry)) {
	echo Graph::bar($type, []);
}
