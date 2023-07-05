<?php

namespace Garradin;

use Garradin\Files\Files;
use Garradin\Files\Storage;
use Garradin\Web\Sync as WebSync;
use Garradin\Entities\Files\File;
use Garradin\UserTemplate\Modules;
use KD2\DB\DB_Exception;
use KD2\DB\EntityManager;

$db->beginSchemaUpdate();

Files::disableQuota();

// There seems to be some plugins table left on some database even when the plugin has been removed
if (!$db->test('plugins', 'id = ?', 'taima')) {
	$db->exec('
		DROP TABLE IF EXISTS plugin_taima_entries;
		DROP TABLE IF EXISTS plugin_taima_tasks;
	');
}

// Get old keys
$config = (object) $db->getAssoc('SELECT key, value FROM config WHERE key IN (\'champs_membres\', \'champ_identifiant\', \'champ_identite\');');

// Create config_users_fields table
$db->exec('
CREATE TABLE IF NOT EXISTS config_users_fields (
    id INTEGER NOT NULL PRIMARY KEY,
    name TEXT NOT NULL,
    sort_order INTEGER NOT NULL,
    type TEXT NOT NULL,
    label TEXT NOT NULL,
    help TEXT NULL,
    required INTEGER NOT NULL DEFAULT 0,
    read_access INTEGER NOT NULL DEFAULT 0,
    write_access INTEGER NOT NULL DEFAULT 1,
    list_table INTEGER NOT NULL DEFAULT 0,
    options TEXT NULL,
    default_value TEXT NULL,
    sql TEXT NULL,
    system TEXT NULL
);');

// Migrate users table
$df = \Garradin\Users\DynamicFields::fromOldINI($config->champs_membres, $config->champ_identifiant, $config->champ_identite, 'numero');
$df->save(false);

$trim_field = function (string $name) use ($db) {
	$db->exec(sprintf("UPDATE users SET %s = TRIM(REPLACE(REPLACE(%1\$s, X'0D' || X'0A', X'0A'), X'0D', X'0A'), ' ' || X'0D' || X'0A') WHERE %1\$s IS NOT NULL AND %1\$s != '';", $db->quoteIdentifier($name)));
};

// Normalize line breaks in user fields, and trim
foreach ($df->all() as $name => $field) {
	if (!$df->isText($name)) {
		continue;
	}

	try {
		$trim_field($name);
	}
	catch (DB_Exception $e) {
		if (false === strpos($e->getMessage(), 'UNIQUE constraint failed')
			|| $name !== $config->champ_identifiant
			|| !$df->get('numero')) {
			throw $e;
		}

		// Change login field if current login field is not unique after trim
		$df->changeLoginField('numero');
		$trim_field($name);
	}
}

// Migrate other stuff
$db->import(ROOT . '/include/migrations/1.3/1.3.0.sql');

// Reindex all files in search, as moving files was broken
$db->exec('DELETE FROM files_search WHERE path NOT LIKE \'web/%\';');

Files::ensureContextsExists();

if (FILE_STORAGE_BACKEND == 'FileSystem') {
	Storage::call(FILE_STORAGE_BACKEND, 'configure', FILE_STORAGE_CONFIG);

	// Move skeletons to new path
	if (file_exists(FILE_STORAGE_CONFIG . '/skel')) {
		if (!file_exists(FILE_STORAGE_CONFIG . '/modules')) {
			@mkdir(FILE_STORAGE_CONFIG . '/modules', 0777, true);
		}

		rename(FILE_STORAGE_CONFIG . '/skel', FILE_STORAGE_CONFIG . '/modules/web');
	}

	// now we store file metadata in DB
	Storage::sync(FILE_STORAGE_BACKEND, FILE_STORAGE_CONFIG);

	WebSync::sync();
}
else {
	// Move skeletons from skel/ to modules/web/
	Files::mkdir('modules/web');
	$db->exec('UPDATE files SET path = REPLACE(path, \'skel/\', \'modules/web\'), parent = REPLACE(parent, \'skel/\', \'modules/web\')
		WHERE parent LIKE \'skel/%\';');
}

// Prepend "./" to includes functions file parameter in web skeletons
foreach (Files::list('modules/web') as $file) {
	if ($file->type == $file::TYPE_DIRECTORY) {
		continue;
	}

	foreach (Files::list(File::CONTEXT_MODULES . '/web') as $file) {
		if ($file->type != File::TYPE_FILE || !preg_match('/\.(?:txt|css|js|html|htm)$/', $file->name)) {
			continue;
		}

		$file->setContent(preg_replace('/(\s+file=")(\w+)/', '$1./$2', $file->fetch()));
	}
}

foreach (Files::listRecursive(null, null, false) as $file) {
	if ($file->context() == $file::CONTEXT_WEB && $file->name == 'index.txt') {
		$file->delete();
		continue;
	}

	$file->indexForSearch();

	if (!$file->md5) {
		// Store file hash
		$file->rehash();
	}

	$file->save();
}

// Migrate web_pages
$db->exec('
	INSERT INTO web_pages
		SELECT id,
			CASE WHEN parent = \'\' THEN NULL ELSE parent END,
			path, \'web/\' || path, uri, type, status, format, published, modified, title, content
		FROM web_pages_old;
	DROP TABLE web_pages_old;
');

// Update searches
foreach ($db->iterate('SELECT * FROM searches;') as $row) {
	if ($row->type == 'json') {
		$json = json_decode($row->content);

		if (!$json) {
			$db->delete('searches', 'id = ?', $row->id);
			continue;
		}

		$json->groups = $json->query;
		unset($json->query, $json->limit);

		$content = json_encode($json);
	}
	else {
		$content = preg_replace('/\bmembres\b/', 'users', $row->content);
	}

	$db->update('searches', ['content' => $content], 'id = ' . (int) $row->id);
}

// Add signature to files
$files = $db->firstColumn('SELECT value FROM config WHERE key = \'files\';');
$files = json_decode($files);
$files->signature = null;
$db->exec(sprintf('REPLACE INTO config (key, value) VALUES (\'files\', %s);', $db->quote(json_encode($files))));

$db->commitSchemaUpdate();

Modules::refresh();
