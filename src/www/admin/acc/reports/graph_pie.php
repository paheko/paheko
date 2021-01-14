<?php
namespace Garradin;

use Garradin\Accounting\Graph;

require_once __DIR__ . '/_inc.php';

header('Content-Type: image/svg+xml');

$expiry = time() - 600;
$hash = sha1('pie_' . json_encode($criterias));

if (!Utils::HTTPCache($hash, $expiry)) {
	echo Graph::pie(qg('type'), $criterias);
}

