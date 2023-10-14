<?php

namespace Paheko;

use Paheko\Files\Files;
use Paheko\Files\Storage;
use Paheko\Web\Sync as WebSync;
use Paheko\Web\Web;
use Paheko\Entities\Files\File;
use Paheko\UserTemplate\Modules;
use KD2\DB\DB_Exception;
use KD2\DB\EntityManager;

$db = DB::getInstance();
$config_path = ROOT . '/' . CONFIG_FILE;

// Rename namespace in config file
if (file_exists($config_path) && is_writable($config_path)) {
	$contents = file_get_contents($config_path);

	$new = strtr($contents, [
		'namespace Garradin' => 'namespace Paheko',
		' Garradin\\' => ' Paheko\\',
		'\'Garradin\\' => '\'Paheko\\',
		'"Garradin\\' => '"Paheko\\',
		'\\Garradin\\' => '\\Paheko\\',
	]);

	if ($new !== $contents) {
		file_put_contents($config_path, $new);
	}
}

$db->beginSchemaUpdate();

Files::disableQuota();
Files::disableVersioning();

// There seems to be some plugins table left on some database even when the plugin has been removed
if (!$db->test('plugins', 'id = ?', 'taima')) {
	$db->exec('
		DROP TABLE IF EXISTS plugin_taima_entries;
		DROP TABLE IF EXISTS plugin_taima_tasks;
	');
}

// We need to drop indexes, are they will be left, but linked to old tables
// and new ones won't be re-created
$db->dropIndexes();

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
	user_access_level INTEGER NOT NULL DEFAULT 0,
	management_access_level INTEGER NOT NULL DEFAULT 1,
    list_table INTEGER NOT NULL DEFAULT 0,
    options TEXT NULL,
    default_value TEXT NULL,
    sql TEXT NULL,
    system TEXT NULL
);');

// Migrate users table
$df = \Paheko\Users\DynamicFields::fromOldINI($config->champs_membres, $config->champ_identifiant, $config->champ_identite, 'numero');
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
$db->exec('DELETE FROM files_search;');

Files::ensureContextsExists();

if (FILE_STORAGE_BACKEND == 'FileSystem') {
	$root = FILE_STORAGE_CONFIG;

	// Move skeletons to new path
	if (file_exists($root . '/skel')) {
		if (!file_exists($root . '/modules')) {
			Utils::safe_mkdir($root . '/modules', 0777, true);
		}

		if (!file_exists($root . '/modules/web')) {
			rename($root . '/skel', $root . '/modules/web');
		}
	}

	Storage::sync();
	WebSync::sync();
}
else {
	// Move files from old table to new
	$db->exec('
		REPLACE INTO files
			SELECT f.id, path, parent, name, type, mime, size, modified, image, md5(fc.content), NULL
			FROM files_old f INNER JOIN files_contents_old fc ON fc.id = f.id;
		REPLACE INTO files_contents (id, content) SELECT id, content FROM files_contents_old;');

	// Move skeletons from skel/ to modules/web/
	Files::mkdir('modules/web');
	$db->exec('UPDATE files SET path = REPLACE(path, \'skel/\', \'modules/web\'), parent = REPLACE(parent, \'skel/\', \'modules/web\')
		WHERE parent LIKE \'skel/%\';');
}

$db->exec('
	DROP TABLE files_contents_old;
	DROP TABLE files_old;
');

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

// Add signature to config files
$files = $db->firstColumn('SELECT value FROM config WHERE key = \'files\';');
$files = json_decode($files);
$files->signature = null;
$db->exec(sprintf('REPLACE INTO config (key, value) VALUES (\'files\', %s);', $db->quote(json_encode($files))));

Modules::refresh();

if ($db->test('sqlite_master', 'type = \'table\' AND name = ?', 'plugin_reservations_categories')) {
	$db->import(__DIR__ . '/1.3.0_bookings.sql');
	$m = Modules::get('bookings');

	if ($m) {
		$m->enabled = true;
		$m->save();
	}
}

$db->commitSchemaUpdate();

$db->begin();

// Delete index.txt files
// This needs to be done AFTER commit schema update as it disables foreign key actions
foreach (Files::all() as $file) {
	if ($file->isDir()) {
		continue;
	}

	if ($file->context() == $file::CONTEXT_WEB && $file->name == 'index.txt') {
		$file->delete();
		continue;
	}

	// Reindex file contents
	$file->indexForSearch();

	// Save files in DB
	$file->save();

}

// Reindex web pages
foreach (Web::listAll() as $page) {
	$page->syncSearch();
}


$db->commit();
