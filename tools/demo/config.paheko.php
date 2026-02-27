<?php

// This file will be copied as 'config.local.php' inside Paheko root

namespace Paheko;

use KD2\ErrorManager;

ErrorManager::setLogFile(__DIR__ . '/error.log');

// Make sure we never disclose errors in production
ErrorManager::setEnvironment(ErrorManager::PRODUCTION);

// Block bots
if (stristr($_SERVER['REQUEST_URI'] ?? '', 'robots.txt')) {
	header('Content-Type: text/plain');
	echo "User-agent: *\n";
	echo "Disallow: /\n";
	exit;
}

// Block bots
if (preg_match('/bot|crawler|spider/i', $_SERVER['HTTP_USER_AGENT'] ?? '')) {
	http_response_code(401);
	exit;
}

require __DIR__ . '/config.local.php';

ErrorManager::setEmail(MAIL_ERRORS);

require __DIR__ . '/functions.php';

/** Paheko config **/

const SHOW_ERRORS = false;
const USE_CRON = true;

const PLUGINS_ROOT = __DIR__ . '/src/paheko-plugins';

const ENABLE_TECH_DETAILS = false;
const ENABLE_UPGRADES = false;

const DISABLE_INSTALL_PING = true;

const WEB_CACHE_ROOT = null;

const OPEN_BASEDIR = 'auto';

const SYSTEM_SIGNALS = [
	['email.queue.before' => __NAMESPACE__ . '\demo_email_check'],
];

function demo_email_check($signal)
{
	$context = $signal->getIn('context');

	// Ignore emails, don't warn
	$signal->stop();

	if ($context !== \Paheko\Email\Emails::CONTEXT_SYSTEM) {
		throw new \Paheko\UserException('L\'envoi d\'e-mail est désactivé.');
	}
}

/** Setting the demo hash **/
$hash = null;

if (preg_match('/^demo-([a-z0-9]+)\./', $_SERVER['SERVER_NAME'] ?? '', $match)) {
	$hash = $match[1];
}

// Hash was supplied in URL
if ($hash) {
	$path = sprintf(DEMO_STORAGE_PATH, $hash);
	if (ctype_alnum($hash)
		&& is_dir($path)
		&& !demo_prune($path)) {
		define('Paheko\DATA_ROOT', $path);
	}
	else {
		http_response_code(404);
		die('<!DOCTYPE html>
			<html style="height: 100%">
			<body style="font-family: sans-serif; display: flex; align-items: center; justify-content: center; flex-direction: column; gap: 1em; height: 100%; font-size: 1.5em; color: darkred;">
			<h1>404</h1>
			<h2>Ce compte de test n\'existe pas ou a expiré.</h2>
			<p><a href="https://' . DEMO_PARENT_DOMAIN . '/">Retour</a></p>');
	}
}
// Demo form
else {
	if (trim($_SERVER['REQUEST_URI'], '/') !== '') {
		header('Location: /');
		exit;
	}

	require __DIR__ . '/form.php';
	exit;
}

// Create cookie when first getting from query parameter
if (isset($_GET['__from'])
	&& $_GET['__from'] === md5($hash . 'from' . SECRET_KEY)
	&& ($id = \apcu_fetch('demo_login_' . $hash))) {
	setcookie('__login', md5($hash . SECRET_KEY), 0);
	apcu_add('demo_user_' . $hash, $id, 300); // 5 minutes
	apcu_delete('demo_login_' . $hash);
	define('Paheko\LOCAL_LOGIN', $id);
}
// Re-create demo-account from local client
elseif (trim($_SERVER['REQUEST_URI'], '/') === ''
	&& isset($_GET['f'])
	&& ctype_alnum($_GET['f'])
	&& ($source = \apcu_fetch('demo_' . $_GET['f']))
	&& 0 === strpos(realpath(sys_get_temp_dir(), realpath($source)))
) {
	\apcu_delete('demo_' . $_GET['f']);
	$id = \apcu_fetch('demo_login_' . $_GET['f']) ?: null;
	\apcu_delete('demo_login_' . $_GET['f']);
	create_demo($source, $id);
	@unlink($source);
}
// Use cookie to create local login to carry logged user
elseif (!empty($_COOKIE['__login'])
	&& ($_COOKIE['__login'] === md5($hash . SECRET_KEY))
	&& ctype_alnum($hash)
	&& ($id = \apcu_fetch('demo_user_' . $hash))) {
	define('Paheko\LOCAL_LOGIN', $id);

	if (!empty($_COOKIE['pko'])) {
		\apcu_delete('demo_user_' . $hash);
	}
}

$days = DEMO_DELETE_DAYS;
$delete_hash = sha1(SECRET_KEY . DATA_ROOT);

if (!empty($_POST['delete_demo']) && $_POST['delete_demo'] === $delete_hash) {
	demo_delete(DATA_ROOT);
	header('Location: /');
	exit;
}

$message = <<<EOF
Compte de test temporaire
— L'envoi d'e-mail est désactivé
— <strong style="color: darkred">Toutes les données seront effacées au bout de {$days} jours&nbsp;!</strong>
— <form method="post" style="display: inline" onsubmit="return confirm('Supprimer le compte de test ?');"><button type="submit" name="delete_demo" value="{$delete_hash}" style="border: 1px solid #999; padding: 1px 2px; background: none; font: inherit; font-size: .8em">Supprimer</button></form>
EOF;

define('Paheko\ALERT_MESSAGE', $message);
