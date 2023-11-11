<?php

namespace Paheko;

if (!empty(getenv('LOCALAPPDATA'))) {
	// Store data in user AppData directory
	define('Paheko\DATA_ROOT', trim(getenv('LOCALAPPDATA'), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'Paheko');

	if (!file_exists(DATA_ROOT)) {
		@mkdir(DATA_ROOT, 0700, true);
	}
}

define('Paheko\PLUGINS_ROOT', __DIR__ . '/data/plugins');

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

// Always log in as admin user
const LOCAL_LOGIN = -1;

// Disable PDF export
const PDF_COMMAND = null;

// Disable e-mails as Windows is not able to send e-mails
const DISABLE_EMAIL = true;
