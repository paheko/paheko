<?php

namespace Paheko;

function create_demo(?string $example = null, ?string $source = null, ?int $user_id = null): void
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
		$hash = sha1(SECRET_KEY . random_bytes(10));
		$hash = base_convert(substr($hash, 0, 8), 16, 36);
		$path = sprintf(DEMO_STORAGE_PATH, $hash);
	}

	$expire = strtotime('tomorrow 02:00');
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
		\apcu_add('demo_login_' . $hash, $user_id);
		$params = '?__from=' . md5($hash . 'from' . SECRET_KEY);
	}

	$url = !empty($_SERVER['HTTPS']) ? 'https' : 'http';
	$url .= '://demo-' . $hash . '.' . DEMO_PARENT_DOMAIN . '/admin/' . $params;

	header('Location: ' . $url);
	exit;
}
