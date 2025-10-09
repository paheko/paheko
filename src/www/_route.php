<?php

namespace Paheko;

use Paheko\Web\Router;
use Paheko\Email\Emails;

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
	$params = array_intersect_key($_GET, ['un' => null, 'v' => null, 'c' => null, 'p' => null]);

	// RFC 8058
	if (!empty($_POST['Unsubscribe']) && $_POST['Unsubscribe'] == 'Yes') {
		$email = Emails::getEmailFromOptout($params['un']);

		if (!$email) {
			throw new UserException('Adresse email introuvable.');
		}

		$email->setOptout($params['c']);
		$email->save();
		http_response_code(200);
		echo 'Unsubscribe successful';
		return;
	}

	Utils::redirect('!optout.php?' . http_build_query($params));
	return;
}
// Handle redirect (rd) URLs for emails: .../?rd=XXXXX
elseif ((empty($uri) || $uri === '/') && !empty($_GET['rd'])) {
	$r = Emails::redirectURL($_GET['rd']);

	$header = '<!DOCTYPE html><meta charset="utf-8" /><style type="text/css">html { height: 100vh; display: flex; justify-content: center; align-items: center; text-align: center; } body { font-family: sans-serif;  }</style><body>';

	if ($r === null) {
		http_response_code(400);
		echo $header;
		echo '<h1>Adresse invalide</h1>';
	}
	else {
		echo $header;
		echo '<h2>Merci de bien vouloir cliquer sur l\'adresse suivante pour être redirigé à l\'adresse demandée&nbsp;:</h2>';
		printf('<h1><a href="%s" rel="noreferrer noopener nofollow">%1$s</a></h1>', htmlspecialchars($r));
	}

	return;
}

// Call router
Router::route();
