<?php

namespace Paheko;

use KD2\Security;

// We don't have autoloader yet here
require_once __DIR__ . '/src/paheko/src/include/lib/KD2/Security.php';
require_once __DIR__ . '/src/paheko/src/include/lib/Paheko/Utils.php';

function demo_prune(string $path): bool
{
	static $expiry = null;
	$expiry ??= time() - (3600 * 24 * DEMO_DELETE_DAYS);

	if (filemtime($path) < $expiry) {
		demo_delete($path);
		return true;
	}

	return false;
}

function demo_delete(string $path): void
{
	Utils::deleteRecursive($path, true);
}

function demo_prune_old(): void
{
	$dir = dir(DEMO_STORAGE_PATH);

	while ($file = $dir->read()) {
		if ($file[0] === '.') {
			continue;
		}

		$path = DEMO_STORAGE_PATH . '/' . $file;

		demo_prune($path);
	}

	$dir->close();
}

function demo_create(?string $example = null, ?string $source = null, ?int $user_id = null): void
{
	if ($example && array_key_exists($example, EXAMPLE_ORGANIZATIONS)) {
		$source = EXAMPLE_ORGANIZATIONS[$example];
	}
	else {
		$example = null;
	}

	if ($source && !file_exists($source)) {
		throw new \InvalidArgumentException('Invalid source: ' . basename($source));
	}

	$path = null;

	while (!$path || file_exists($path)) {
		$key = Security::getRandomPassword(random_int(10, 20), 'abcdefghijkmnopqrstuvwxyz1234567890');
		$path = sprintf(DEMO_STORAGE_PATH, $key);
	}

	mkdir($path, 0777, true);

	if ($source) {
		copy($source, $path . '/association.sqlite');
	}

	// Force login and password
	if ($example) {
		$db = new \SQLite3($path  . '/association.sqlite');
		// Overwrite
		$user_id = (int) $db->querySingle('SELECT id FROM users WHERE id_category IN (SELECT id FROM users_categories WHERE perm_config = 9 AND perm_users = 9 AND perm_connect = 1) ORDER BY id LIMIT 1;', false);
		$db->exec('UPDATE users SET password = \'' . $db->escapeString(password_hash('paheko', PASSWORD_DEFAULT)) . '\', email = \'demo@' . DEMO_PARENT_DOMAIN . '\' WHERE id = ' . $user_id . ';');
		$db->close();
	}

	$params = '';

	if ($user_id) {
		\apcu_add('demo_login_' . $key, $user_id);
		$params = '?__from=' . md5($key . 'from' . SECRET_KEY);
	}

	$url = !empty($_SERVER['HTTPS']) ? 'https' : 'http';
	$url .= '://demo-' . $key . '.' . DEMO_PARENT_DOMAIN . '/admin/' . $params;

	header('Location: ' . $url);
}
