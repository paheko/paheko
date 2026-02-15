<?php

namespace Paheko;
use Paheko\Accounting\Charts;

$db->beginSchemaUpdate();
Charts::updateInstalled('fr_pca_2025');

$db->import(__DIR__ . '/1.3.18.sql');

foreach ($db->iterate('SELECT id, fail_log FROM emails WHERE fail_log IS NOT NULL;') as $row) {
	$log = explode("\n", $row->fail_log);

	foreach ($log as $line) {
		$line = explode(' - ', $line, 2);
		$date = \DateTime::createFromFormat('d/m/Y H:i:s', $line[0]);

		if (!$date) {
			continue;
		}

		$db->insert('emails_addresses_events', [
			'id_email' => $row->id,
			'date'     => $date,
			'details'  => str_replace('<CRLF>', "\n", $line[1]),
			'type'     => null
		]);
	}
}

$db->exec('DROP TABLE emails;');

$db->commitSchemaUpdate();

// Delete all WAL/SHM files by making sure the journal mode is DELETE
foreach (glob(DATA_ROOT . '/*.sqlite-wal') as $file) {
	$name = basename($file);
	$name = str_replace('-wal', '', $name);

	if ($name === basename(DB_FILE)) {
		continue;
	}

	if (0 !== strpos($file, 'association.')) {
		continue;
	}

	$ldb = new \SQLite3(DATA_ROOT . DIRECTORY_SEPARATOR . $name);
	$ldb->exec('PRAGMA journal_mode = DELETE;');
	$ldb->exec('VACUUM;');
	$ldb->close();
}

// Move backups to new subdirectory, and rename them to new naming schema
foreach (glob(DATA_ROOT . '/*.sqlite') as $src) {
	$file = Utils::basename($src);

	if ($file === basename(DB_FILE)) {
		continue;
	}

	if (0 !== strpos($file, 'association.')) {
		continue;
	}

	$name = str_replace(['association.', '.sqlite'], '', $file);

	if (preg_match('/^(.*)-avant-remise-a-zero$/', $name, $match)) {
		$name = Backup::RESET_PREFIX . $match[1];
	}
	elseif (preg_match('/^pre[_-]upgrade[-_](.*)$/', $name, $match)) {
		$name = Backup::UPGRADE_PREFIX . $match[1];
	}
	elseif (preg_match('/^auto\.(\d+)$/', $name, $match)) {
		$name = Backup::AUTO_PREFIX . date('YmdHis', filemtime($src));
	}

	$name = Backup::PREFIX . $name . Backup::SUFFIX;

	rename(DATA_ROOT . DIRECTORY_SEPARATOR . $file, BACKUPS_ROOT . DIRECTORY_SEPARATOR . $name);
}
