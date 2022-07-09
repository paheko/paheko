<?php

namespace Garradin;

use Garradin\Membres\Session;

if (PHP_SAPI != 'cli') {
	die("Wrong call");
}

require_once __DIR__ . '/../include/init.php';

$id = readline('User ID: ');
$pw = readline('Password: ');

if (!$id || !$pw) {
	exit;
}

$id = (int) $id;
$pw = trim($pw);
$pw = Session::hashPassword($pw);

DB::getInstance()->preparedQuery('UPDATE membres SET passe = ? WHERE id = ?;', $pw, $id);
echo "OK\n";
