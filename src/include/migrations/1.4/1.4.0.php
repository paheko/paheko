<?php

namespace Paheko;

use Paheko\Accounting\Charts;

Charts::resetRules(['FR']);

$db->beginSchemaUpdate();
$db->import(ROOT . '/include/migrations/1.4/1.4.0.sql');

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
