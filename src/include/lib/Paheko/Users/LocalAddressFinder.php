<?php

namespace Paheko\Users;

use Paheko\Config;
use Paheko\Static_Cache;
use KD2\HTTP;

use const Paheko\LOCAL_ADDRESSES_ROOT;

/**
 * See https://fossil.kd2.org/paheko/tktview/0ebd4b643fc4f97d903445e7632d91b7968d964f
 * and tools/build_address_database_fr.php
 */
class LocalAddressFinder
{
	static protected $db = [];
	static protected $statements = [];

	static public function search(string $country, string $search): ?array
	{
		if (!LOCAL_ADDRESSES_ROOT || !trim($search)) {
			return null;
		}

		$country = strtolower($country);

		if (strlen($country) !== 2 || !ctype_alpha($country)) {
			throw new \InvalidArgumentException('Invalid country: ' . $country);
		}

		$path = rtrim(LOCAL_ADDRESSES_ROOT, '/') . '/' . $country . '.sqlite';

		if (!file_exists($path)) {
			return null;
		}

		self::$db[$country] ??= new \SQLite3($path, \SQLITE3_OPEN_READONLY);
		$db = self::$db[$country];

		$config = $db->querySingle('SELECT number_regexp, street_regexp FROM config;', true);

		// Cleanup search query
		$search = preg_replace('/[^\da-z\p{L}\s]+/iu', ' ', $search);
		$search = trim($search);

		if (!$search) {
			return null;
		}

		// Put all strings between quotes, as required by FTS5
		// see https://www.sqlite.org/fts5.html#full_text_query_syntax
		$regexp = sprintf('/%s|%s|[^\s]+/i', $config['number_regexp'], $config['street_regexp']);
		$query = preg_replace($regexp, '"$0"', $search);
		$query = preg_replace('/\s{2,}/', ' ', $query);
		$query = trim($query);

		try {
			self::$statements[$country] ??= $db->prepare('SELECT *, rank FROM search WHERE search MATCH ? ORDER BY rank DESC LIMIT 10;');
			$st = self::$statements[$country];
			$st->bindValue(1, $query);

			$result = $st->execute();
		}
		catch (DB_Exception $e) {
			if (strpos($e->getMessage(), 'fts5: syntax error') !== false) {
				return null;
			}

			throw $e;
		}

		$number = null;

		if (preg_match('/^\d+[a-z]?(?: (?:bis|ter|quater))?\b/i', $search, $match)) {
			$number = $match[0] . ' ';
		}

		$out = [];

		while ($row = $result->fetchArray(\SQLITE3_ASSOC)) {
			unset($row['numbers'], $row['rank']);
			$row['address'] = $number . $row['street'];
			$row['label'] = $row['address'] . ', ' . $row['code'] . ' ' . $row['city'];
			$out[] = $row;
		}

		$st->reset();
		$st->clear();
		return $out;
	}
}
