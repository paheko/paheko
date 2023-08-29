<?php

namespace Paheko;

$db->exec('BEGIN;
	REPLACE INTO config VALUES (\'file_versioning_policy\', \'none\');
	REPLACE INTO config VALUES (\'file_versioning_max_size\', 5);
	UPDATE web_pages SET format = \'encrypted\' WHERE format = \'skriv/encrypted\';
	COMMIT;');
