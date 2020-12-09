<?php
namespace Garradin;

use Garradin\Accounting\Graph;

require_once __DIR__ . '/_inc.php';

header('Content-Type: image/svg+xml');

$expiry = time() + 1800;
$hash = sha1('plot_' . json_encode($criterias));

if (!Utils::HTTPCache($hash, $expiry)) {
	echo Graph::plot(qg('type'), $criterias);
}

