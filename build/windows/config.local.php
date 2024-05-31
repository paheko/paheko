<?php

namespace Paheko;

$local_app_data_root = null;

if (!empty(getenv('LOCALAPPDATA'))) {
	$local_app_data_root = trim(getenv('LOCALAPPDATA'), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'Paheko';
}

const USER_CONFIG_FILE = DATA_ROOT . '/config.local.php';

if (file_exists(USER_CONFIG_FILE)) {
	require USER_CONFIG_FILE;
}

if (!defined('Paheko\DATA_ROOT')) {
	// Store data in user AppData directory
	define('Paheko\DATA_ROOT', $local_app_data_root);

	if (!file_exists(DATA_ROOT)) {
		@mkdir(DATA_ROOT, 0700, true);
	}
}

if (!defined('Paheko\PLUGINS_ROOT')) {
	define('Paheko\PLUGINS_ROOT', __DIR__ . '/data/plugins');
}

// Store secret key in user directory
if (!defined('Paheko\SECRET_KEY')) {
	if (file_exists(DATA_ROOT . '/key')) {
		define('Paheko\SECRET_KEY', trim(file_get_contents(DATA_ROOT . '/key')));
	}
	else {
		define('Paheko\SECRET_KEY', base64_encode(random_bytes(16)));
		file_put_contents(DATA_ROOT . '/key', SECRET_KEY);
	}
}

if (!defined('Paheko\LOCAL_LOGIN')) {
	// Always log in as admin user
	define('Paheko\LOCAL_LOGIN', -1);
}

if (!defined('Paheko\PDF_COMMAND')) {
	// Always log in as admin user
	define('Paheko\PDF_COMMAND', null);
}

if (!defined('Paheko\DISABLE_EMAIL')) {
	// Disable e-mails as Windows is not able to send e-mails
	define('Paheko\DISABLE_EMAIL', true);
}
