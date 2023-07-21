<?php
namespace Paheko;

use Paheko\Accounting\Graph;

require_once __DIR__ . '/_inc.php';

header('Content-Type: image/svg+xml');

$expiry = time() - 30;
$hash = sha1('graph_plot_' . json_encode($criterias));

if (!Utils::HTTPCache($hash, $expiry)) {
	echo Graph::plot(qg('type'), $criterias);
}

