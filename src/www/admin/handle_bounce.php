<?php

namespace Paheko;

use Paheko\Email\Emails;

require_once __DIR__ . '/../../include/init.php';

function error(int $http_code, string $message)
{
	$http_statuses = [
		202 => 'Accepted',
		400 => 'Bad Request',
		401 => 'Unauthorized',
		402 => 'Payment Required',
		403 => 'Forbidden',
		404 => 'Not Found',
		405 => 'Method Not Allowed',
		406 => 'Not Acceptable',
		407 => 'Proxy Authentication Required',
		408 => 'Request Timeout',
		409 => 'Conflict',
	];

	header(sprintf('%s %d %s', $_SERVER['SERVER_PROTOCOL'], $http_code, $http_statuses[$http_code]), true, $http_code);
	echo $message . PHP_EOL;
	exit;
}

if (empty(MAIL_BOUNCE_PASSWORD) || empty($_SERVER['PHP_AUTH_USER']) || empty($_SERVER['PHP_AUTH_PW'])
	|| $_SERVER['PHP_AUTH_USER'] != 'bounce' || $_SERVER['PHP_AUTH_PW'] != MAIL_BOUNCE_PASSWORD)
{
	error(403, 'Invalid credentials');
}

if (empty($_POST['message']))
{
	error(400, 'Missing or invalid required parameters');
}

Emails::handleBounce($_POST['message']);

error(202, 'OK');
