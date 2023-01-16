<?php

namespace Garradin;

if (!empty(getenv('LOCALAPPDATA'))) {
	// Store data in user AppData directory
	define('Garradin\DATA_ROOT', trim(getenv('LOCALAPPDATA'), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'Paheko');
}

// Store secret key in user directory
if (!defined('Garradin\SECRET_KEY')) {
	if (file_exists(DATA_ROOT . '/key')) {
		define('Garradin\SECRET_KEY', trim(file_get_contents(DATA_ROOT . '/key')));
	}
	else {
		define('Garradin\SECRET_KEY', base64_encode(random_bytes(16)));
		file_put_contents(DATA_ROOT . '/key', SECRET_KEY);
	}
}

// Always log in as admin user
const LOCAL_LOGIN = -1;
