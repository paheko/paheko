<?php

namespace Paheko;

use Paheko\Web\Router;
use Paheko\Email\Addresses;

if (empty($_SERVER['REQUEST_URI'])) {
	http_response_code(500);
	die('Appel non supporté');
}

$uri = $_SERVER['REQUEST_URI'];

if ('_route.php' === basename($uri)) {
	http_response_code(403);
	die('Appel interdit');
}

http_response_code(200);

if (($pos = strpos($uri, '?')) !== false) {
	$uri = substr($uri, 0, $pos);
}

if (PHP_SAPI === 'cli-server' && $uri !== '/' && file_exists(__DIR__ . $uri)) {
	return false;
}

// Include init.php in a function so that its variables definitions don't affect us
// (eg. $uri)
(function () { require(__DIR__ . '/../include/init.php'); })();

// Handle __un__subscribe URL: .../?un=XXXX
if ((empty($uri) || $uri === '/') && !empty($_GET['un'])) {
	$params = array_intersect_key($_GET, ['un' => null, 'v' => null]);

	// RFC 8058
	if (!empty($_POST['Unsubscribe']) && $_POST['Unsubscribe'] == 'Yes') {
		$email = Addresses::getFromOptout($params['un']);

		if (!$email) {
			throw new UserException('Adresse email introuvable.');
		}

		$email->setOptout();
		$email->save();
		http_response_code(200);
		echo 'Unsubscribe successful';
		return;
	}

	Utils::redirect('!optout.php?' . http_build_query($params));
	return;
}

// Call router
Router::route();
