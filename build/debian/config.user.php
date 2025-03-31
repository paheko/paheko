<?php
/**
 * This file is used for Paheko Debian package,
 * when using Paheko as a system-wide setup (eg. with a web server)
 */
namespace Paheko;

$home = $_ENV['HOME'];

// Config directory
if (empty($_ENV['XDG_CONFIG_HOME'])) {
	$_ENV['XDG_CONFIG_HOME'] = $home . '/.config';
}

// Data directory: where the data will go
if (empty($_ENV['XDG_DATA_HOME'])) {
	$_ENV['XDG_DATA_HOME'] = $home . '/.local/share';
}

// Cache directory: temporary stuff
if (empty($_ENV['XDG_CACHE_HOME'])) {
	$_ENV['XDG_CACHE_HOME'] = $home . '/.cache';
}

// Rename Garradin to Paheko
if (file_exists($_ENV['XDG_CONFIG_HOME'] . '/garradin')) {
	rename($_ENV['XDG_CONFIG_HOME'] . '/garradin', $_ENV['XDG_CONFIG_HOME'] . '/paheko');
}

if (file_exists($_ENV['XDG_DATA_HOME'] . '/garradin')) {
	rename($_ENV['XDG_DATA_HOME'] . '/garradin', $_ENV['XDG_DATA_HOME'] . '/paheko');
}

if (file_exists($_ENV['XDG_CACHE_HOME'] . '/garradin')) {
	rename($_ENV['XDG_CACHE_HOME'] . '/garradin', $_ENV['XDG_CACHE_HOME'] . '/paheko');
}

if (!file_exists($_ENV['XDG_CONFIG_HOME'] . '/paheko')) {
	mkdir($_ENV['XDG_CONFIG_HOME'] . '/paheko', 0700, true);
}

const ENABLE_UPGRADES = false;
const SQLITE_JOURNAL_MODE = 'wal';

define('Paheko\DESKTOP_CONFIG_FILE', $_ENV['XDG_CONFIG_HOME'] . '/paheko/config.local.php');

if (file_exists(DESKTOP_CONFIG_FILE)) {
	require_once DESKTOP_CONFIG_FILE;
}

if (!defined('Paheko\DATA_ROOT')) {
	define('Paheko\DATA_ROOT', $_ENV['XDG_DATA_HOME'] . '/paheko');
}

if (!defined('Paheko\DB_FILE')) {
	define('Paheko\DB_FILE', $_ENV['XDG_DATA_HOME'] . '/paheko/association.sqlite');
}

if (!defined('Paheko\CACHE_ROOT')) {
	define('Paheko\CACHE_ROOT', $_ENV['XDG_CACHE_HOME'] . '/paheko');
}

if (!defined('Paheko\LOCAL_LOGIN')) {
	define('Paheko\LOCAL_LOGIN', -1);
}

if (!defined('Paheko\CONVERSION_TOOLS')) {
	$tools = [];

	if (shell_exec('which mutool')) {
		$tools[] = 'mupdf';
	}

	if (shell_exec('which ssconvert')) {
		$tools[] = 'ssconvert';
	}

	if (shell_exec('which ffmpeg')) {
		$tools[] = 'ffmpeg';
	}

	define('Paheko\CONVERSION_TOOLS', $tools);
}

@mkdir(CACHE_ROOT, 0700, true);
@mkdir(DATA_ROOT, 0700, true);

if (!defined('Paheko\SECRET_KEY')) {
	if (file_exists(CACHE_ROOT . '/key')) {
		$key = trim(file_get_contents(CACHE_ROOT . '/key'));
	}
	else {
		$key = base64_encode(random_bytes(64));
		file_put_contents(CACHE_ROOT . '/key', $key);
	}

	define('Paheko\SECRET_KEY', $key);
}
