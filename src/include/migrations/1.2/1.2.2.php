<?php

// Put back bank accounts that were in 51* but not in 512* back in 512 subclass
$sql = 'SELECT a.*, c.code AS chart_code, c.country
	FROM acc_accounts AS a
	INNER JOIN acc_charts AS c ON c.id = a.id_chart
	WHERE a.code LIKE \'51%\' AND a.type = 0 AND a.code NOT LIKE \'517%\' AND a.code NOT LIKE \'518%\'
	AND (SELECT COUNT(*) FROM acc_transactions_lines AS b WHERE b.id_account = a.id) > 0
	AND c.country = \'FR\'
	GROUP BY a.id;';

$db->begin();

foreach ($db->iterate($sql) as $row) {
	$new_code = '5120' . substr($row->code, 2);

	if ($db->firstColumn('SELECT 1 FROM acc_accounts WHERE code = ? AND id_chart = ?;', $new_code, $row->id_chart)) {
		$new_code = '51200' . substr($row->code, 2);
	}

	// Change code
	$db->preparedQuery('UPDATE acc_accounts SET code = ?, type = 1, user = ? WHERE id = ?;', [$new_code, $row->chart_code ? 1 : 0, $row->id]);

	// If the account was part of the official chart, put it back in the chart
	if ($row->chart_code && !$row->user) {
		$db->preparedQuery('INSERT INTO acc_accounts (id_chart, code, label, position, user) VALUES (?, ?, ?, ?, ?);',
			[$row->id_chart, $row->code, $row->label, $row->position, 0]);
	}
}

$db->commit();
