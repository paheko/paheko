<?php

namespace Paheko;

function create_demo(?string $example = null, ?string $source = null, ?int $user_id = null): void
{
	if (in_array($example, EXAMPLE_ORGANIZATIONS, true)) {
		$source = EXAMPLE_ORGANIZATIONS[$example];
	}

	if ($source && !file_exists($source . '/association.sqlite')) {
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
		copy($source . '/association.sqlite', $path . '/association.sqlite');
		$source_hash = basename(realpath($source));
		$source_files_path = $source . '/files';

		$db = new \SQLite3($path  . '/association.sqlite');
		$db->exec('PRAGMA foreign_keys = ON; BEGIN;');
		$total_size = 0;

		// Copy files inside database
		$res = $db->query('SELECT id, path, size FROM files WHERE type = 1;');

		while ($row = $res->fetchArray(\SQLITE3_ASSOC)) {
			if ($total_size >= FILE_STORAGE_QUOTA) {
				break;
			}

			// Just in case someone attempts something weird
			if (false !== strpos($row['path'], '..')) {
				break;
			}

			$id = $row['id'];
			$src_path = realpath($source_files_path . '/' . $row['path']);

			if (!$src_path) {
				continue; // file does not exist in source, skip
			}

			if (0 !== strpos($src_path, $source_files_path)) {
				throw new \LogicException(sprintf('File path "%s" is outside of base path "%s"', $src_path, $source_files_path));
			}

			$db->exec(sprintf('INSERT OR REPLACE INTO files_contents (id, content) VALUES (%d, zeroblob(%d));', $id, $row['size']));

			$blob = $db->openBlob('files_contents', 'content', $id, 'main', \SQLITE3_OPEN_READWRITE);
			$pointer = fopen($src_path, 'rb');

			while (!feof($pointer)) {
				$bytes = fread($pointer, 8192);
				fwrite($blob, $bytes);
				$total_size += strlen($bytes);
			}

			fclose($pointer);
			fclose($blob);
		}

		// Delete files that could not be copied because quota has been exceeded
		// don't delete directories or it will also delete files inside them (via foreign keys)
		$db->exec('DELETE FROM files WHERE type = 1 AND id NOT IN (SELECT id FROM files_contents);');

		$db->exec('COMMIT;');

		// Force login and password
		if (in_array($example, EXAMPLE_ORGANIZATIONS, true)) {
			// Overwrite
			$user_id = (int) $db->querySingle('SELECT id FROM users WHERE id_category IN (SELECT id FROM users_categories WHERE perm_config = 9 AND perm_users = 9 AND perm_connect = 1) ORDER BY id LIMIT 1;', false);
			$db->exec('UPDATE users SET password = \'' . $db->escapeString(password_hash('paheko', PASSWORD_DEFAULT)) . '\', email = \'demo@' . DEMO_PARENT_DOMAIN . '\' WHERE id = ' . $user_id . ';');
		}

		$db->close();

		// reopen to vacuum, if we just vacuum then we might get an error
		// because of the blob pointers, even though they should be closed?
		// https://stackoverflow.com/questions/41516542/sqlite-error-statements-in-progress-when-no-statements-should-be#comment127004699_41516542
		$db = new \SQLite3($path  . '/association.sqlite');
		$db->exec('VACUUM;');
		$db->close();
	}

	$params = '';

	if ($user_id) {
		\apcu_add('demo_login_' . $hash, $user_id);
		$params = '?__from=' . md5($hash . 'from' . SECRET_KEY);
	}

	$url = !empty($_POST['HTTPS']) ? 'https' : 'http';
	$url .= '://demo-' . $hash . '.' . DEMO_PARENT_DOMAIN . '/admin/' . $params;

	header('Location: ' . $url);
	exit;
}
