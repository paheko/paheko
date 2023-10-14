<?php

namespace Paheko;

const LOGIN_PROCESS = true;

// Forbid bots from indexing this
if (stristr($_SERVER['HTTP_USER_AGENT'] ?? '', 'bot')) {
	http_response_code(403);
	return;
}

require_once __DIR__ . '/_inc.php';

$tpl->display('legal.tpl');
