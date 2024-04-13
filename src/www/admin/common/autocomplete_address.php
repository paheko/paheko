<?php

namespace Paheko;

use Paheko\Users\LocalAddressFinder;

require_once __DIR__ . '/../../../include/init.php';

if (empty($_POST['search'])) {
	throw new UserException('Invalid request', 400);
}

$options = stristr($_SERVER['HTTP_USER_AGENT'] ?? '', 'curl') ? JSON_PRETTY_PRINT : 0;

$params = [
	'q' => $_POST['search'],
];

header('Content-Type: application/json; charset=utf-8');
echo json_encode(LocalAddressFinder::search('FR', $_POST['search']), $options);
