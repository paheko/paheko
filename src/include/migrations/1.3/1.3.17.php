<?php

namespace Paheko;

$file = DATA_ROOT . '/association.pre_upgrade-1.3.16.sqlite';

// Re-import file shares from previous save, if it exists
// as they might have been deleted by the file storage sync method
if (!file_exists($file)) {
	return;
}

$db->toggleForeignKeys(true);
$db->disableSafetyAuthorizer();
$db->exec(sprintf('ATTACH %s AS bckup;', $db->quote($file)));

// use the MD5 hash to join files, as the ID may have changed,
// because of the previous bug
$db->exec('INSERT OR IGNORE INTO files_shares
	SELECT fs2.id, f1.id, u.id, fs2.created, fs2.hash_id, fs2.option, fs2.expiry, fs2.password
	FROM bckup.files_shares AS fs2
	INNER JOIN bckup.files AS f2 ON f2.id = fs2.id_file
	INNER JOIN files AS f1 ON f1.md5 IS NOT NULL AND f1.md5 = f2.md5
	INNER JOIN users u ON u.id = fs2.id_user
	GROUP BY fs2.id;
DETACH bckup;
');

$db->enableSafetyAuthorizer();