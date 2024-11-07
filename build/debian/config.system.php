<?php
/**
 * This file is used for Paheko Debian package,
 * when using Paheko as a system-wide setup (eg. with a web server)
 */
namespace Paheko;

// Stop here if config file is already defined, as this means we are in user-mode
// (see config.user.php)
if (defined(__NAMESPACE__ . '\CONFIG_FILE')) {
	return;
}

const SQLITE_JOURNAL_MODE = 'WAL';
const ENABLE_UPGRADES = false;

if (file_exists('/etc/paheko/config.php')) {
	require_once '/etc/paheko/config.php';
}

if (!defined('Paheko\DATA_ROOT')) {
	define('Paheko\DATA_ROOT', '/var/lib/paheko');
}

if (!defined('Paheko\CACHE_ROOT')) {
	define('Paheko\CACHE_ROOT', '/var/cache/paheko');
}

if (!defined('Paheko\SECRET_KEY')) {
	if (file_exists(CACHE_ROOT . '/key')) {
		define('Paheko\SECRET_KEY', trim(file_get_contents(CACHE_ROOT . '/key')));
	}
	else {
		define('Paheko\SECRET_KEY', base64_encode(random_bytes(64)));
		file_put_contents(CACHE_ROOT . '/key', SECRET_KEY);
	}
}
