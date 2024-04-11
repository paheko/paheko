<?php

/**
 * Ce script est utilisé pour construire la base de données
 */

$db_file = $_SERVER['args'][1] ?? 'fr.sqlite';
$tmp_path = sys_get_temp_dir();

if (!file_exists($tmp_path . '/full.csv')) {
	if (!file_exists($tmp_path . '/full.csv.gz')) {
		// see https://bano.openstreetmap.fr/data/
		passthru('wget https://bano.openstreetmap.fr/data/full.csv.gz -O ' . escapeshellarg($tmp_path . '/full.csv.gz'));
	}

	passthru('gunzip ' . escapeshellarg($tmp_path . '/full.csv.gz'));
}

if (!file_exists($tmp_path . '/full.csv')) {
	die("Can't download file\n");
}

@unlink($db_file);

echo "Writing to $db_file\n";
$db = new \SQLite3($db_file);

$number_regexp = '^\d+[a-z]?(?: (?:bis|ter|quater))?\b';
$street_regexp = '(?:impasse|rue|place|chemin|route|residence|avenue|montee|allee|lotissement)\s+(?:(?:du|de la|des|de l\'|de) )?[^ ]+';

// Just make sure regexp is OK
if (false === preg_match($number_regexp, '')) {
	exit(1);
}

if (false === preg_match($street_regexp, '')) {
	exit(1);
}

$number_regexp = $db->escapeString($number_regexp);
$street_regexp = $db->escapeString($street_regexp);

$db->createFunction('gzdeflate', 'gzdeflate');
$db->createFunction('gzinflate', 'gzinflate');
$db->exec('
	CREATE TABLE config (number_regexp TEXT NOT NULL, street_regexp TEXT NOT NULL);
	INSERT INTO config VALUES (\'' . $number_regexp . '\', \'' . $street_regexp. '\');

	CREATE TABLE cities (
		code TEXT NOT NULL,
		name TEXT NOT NULL
	);

	CREATE TABLE prefixes (
		name TEXT NOT NULL
	);

	CREATE TABLE streets (
		city INTEGER NOT NULL REFERENCES cities (rowid) ON DELETE CASCADE,
		prefix INTEGER NULL REFERENCES prefixes (rowid) ON DELETE CASCADE,
		name TEXT NOT NULL
	);

	CREATE TABLE numbers (
		street INTEGER NOT NULL REFERENCES streets (rowid) ON DELETE CASCADE,
		number TEXT NOT NULL,
		lat REAL,
		lon REAL
	);

	CREATE UNIQUE INDEX cities_unique ON cities (code, name);
	CREATE UNIQUE INDEX prefixes_unique ON prefixes (name);
	CREATE UNIQUE INDEX streets_unique ON streets (city, prefix, name);
	CREATE UNIQUE INDEX numbers_unique ON numbers (street, number);
');

$fp = fopen($tmp_path . '/full.csv', 'r');
$db->exec('BEGIN;');
$i = 0;
$max = 26413465;
$current_code = null;
$current_city = null;
$current_street = null;

while (!feof($fp)) {
	$i++;
	$line = fgetcsv($fp, 4096);

	if (empty($line)) {
		continue;
	}

	if ($current_code !== $line[3] || $current_city !== $line[4]) {
		$current_code = $line[3];
		$current_city = $line[4];
		// Some codes contain only 4 digits, and some contain multiple codes, eg. '97610;97615'
		$code = substr('0' . substr($current_code, 0, 5), -5);
		$city = insert('cities', ['code' => $code, 'name' => $current_city]);
		$street = null;
		$current_street = null;
	}

	if ($current_street !== $line[2]) {
		if (!empty($line[2])) {
			$current_street = $line[2];
			$name = $current_street;
			$prefix = null;

			if (false && preg_match($prefixes_regexp, strtolower($name), $match)) {
				$p = trim($match[0]);
				$name = ltrim(substr($name, strlen($match[0])));
				$prefix = insert('prefixes', ['name' => trim($p)]);
			}

			$street = insert('streets', ['city' => (int) $city, 'name' => $name]);
		}
		else {
			echo "Missing street info: " . implode(";", $line) . "\n";
		}
	}

	if ($street && !empty($line[1])) {
		insert('numbers', ['street' => (int)$street, 'number' => $line[1], 'lat' => $line[6], 'lon' => $line[7]], ['street', 'number']);
	}

	if ($i % 100000 == 0) {
		echo (1-($i / $max))*100 . " ";
		$db->exec('END; BEGIN;');
	}
}

$db->exec('END;');

/*
$db->exec('
	CREATE VIRTUAL TABLE streets_search USING fts5 (tokenize = \'unicode61\', name, content=streets);
	INSERT INTO streets_search SELECT name FROM streets;
	CREATE VIRTUAL TABLE cities_search USING fts5 (tokenize = \'unicode61\', name, content=cities);
	INSERT INTO cities_search SELECT name FROM cities;
	VACUUM;
');
*/

$db->exec('
	CREATE VIRTUAL TABLE search USING fts5 (tokenize = \'unicode61\', numbers, street, code, city);
	INSERT INTO search SELECT GROUP_CONCAT(n.number, \' \'), s.name, c.code, c.name
		FROM streets s
		INNER JOIN cities c ON s.city = c.rowid
		INNER JOIN numbers n ON n.street = s.rowid
		GROUP BY s.rowid;
	DROP TABLE streets;
	DROP TABLE cities;
	DROP TABLE numbers;
	DROP TABLE prefixes;
	VACUUM;
');

fclose($fp);

$statements = [];

function query(string $sql, array $params)
{
	global $db, $statements;

	$statements[$sql] ??= $db->prepare($sql);
	$st = $statements[$sql];

	$i = 1;
	foreach ($params as $p) {
		$st->bindValue($i++, $p, is_int($p) ? \SQLITE3_INTEGER : \SQLITE3_TEXT);
	}

	$r = $st->execute();

	if ($st->readOnly()) {
		$r = $r->fetchArray(\SQLITE3_NUM)[0] ?? null;
	}
	else {
		$r = null;
	}

	$st->reset();
	$st->clear();

	return $r;
}

function insert(string $table, array $params, ?array $keys = null): int
{
	global $db;

	$columns = implode(',', array_keys($params));
	$keys ??= array_keys($params);

	$where = '';
	$where_params = [];

	foreach ($keys as $key) {
		$where .= ' AND ' . $key . ' = ?';
		$where_params[] = $params[$key];
	}

	$sql = sprintf('SELECT rowid FROM %s WHERE %s;', $table, substr($where, 4));
	$id = query($sql, $where_params);

	if ($id) {
		return (int) $id;
	}

	$sql = sprintf('INSERT OR IGNORE INTO %s (%s) VALUES (%s);', $table, $columns, substr(str_repeat('?, ', count($params)), 0, -2));
	query($sql, $params);
	return $db->lastInsertRowID();
}