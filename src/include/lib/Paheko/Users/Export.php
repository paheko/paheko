<?php
declare(strict_types=1);

namespace Paheko\Users;

use Paheko\CSV;
use Paheko\DB;

class Export
{
	static public function exportSelected(string $format, array $ids): void
	{
		$db = DB::getInstance();

		$ids = array_map('intval', $ids);
		$where = 'u.' . $db->where('id', $ids);
		$name = sprintf('Liste de %d membres', count($ids));
		self::exportWhere($format, $name, $where);
	}

	static public function exportCategory(string $format, int $id_category, bool $with_id = false): void
	{
		if ($id_category == -1) {
			$name = 'Tous les membres';
			$where = '1';
		}
		elseif (!$id_category) {
			$name = 'Membres sauf catégories cachées';
			$where = 'u.id_category NOT IN (SELECT id FROM users_categories WHERE hidden = 1)';
		}
		else {
			$cat = Categories::get($id_category);

			if (!$cat) {
				throw new \InvalidArgumentException('This category does not exist');
			}

			$name = sprintf('Membres - %s', $cat->name);
			$where = sprintf('u.id_category = %d', $id_category);
		}

		self::exportWhere($format, $name, $where, $with_id);
	}

	static public function export(string $format, bool $with_id = false): void
	{
		self::exportWhere($format, 'Tous les membres', '1', $with_id);
	}

	static protected function exportWhere(string $format, string $name, string $where, bool $with_id = false): void
	{
		$df = DynamicFields::getInstance();
		$db = DB::getInstance();

		$tables = 'users_view u';
		$header = $df->listAssocNames();

		if ($with_id) {
			$header = array_merge(['id' => 'id'], $header);
		}

		$columns = array_combine(array_keys($header), array_keys($header));
		$columns = array_map([$db, 'quoteIdentifier'], $columns);

		if (Users::hasParents()) {
			$columns = array_map(fn($a) => 'u.' . $a, $columns);
			$tables .= ' LEFT JOIN users b ON b.id = u.id_parent';
			$tables .= ' LEFT JOIN users c ON c.id_parent = u.id';

			$columns[] = sprintf('CASE WHEN u.id_parent IS NOT NULL THEN %s ELSE NULL END AS parent_name', $df->getNameFieldsSQL('b'));
			$columns[] = sprintf('CASE WHEN u.is_parent THEN GROUP_CONCAT(%s, \'%s\') ELSE NULL END AS children_names', $df->getNameFieldsSQL('c'), "\n");

			$header['parent_name'] = 'Rattaché à';
			$header['children_names'] = 'Membres rattachés';
		}

		foreach ($df->fieldsByType('file') as $name => $field) {
			$columns[$name] = sprintf('(SELECT GROUP_CONCAT(name, x\'0a\') FROM files AS f INNER JOIN users_files uf ON uf.id_file = f.id WHERE uf.field = %s AND uf.id_user = u.id) AS %s', $db->quote($name), $db->quoteIdentifier($name));
		}

		$columns = implode(', ', $columns);
		$header['category'] = 'Catégorie';

		$i = $db->iterate(sprintf('SELECT %s, (SELECT name FROM users_categories WHERE id = u.id_category) AS category
			FROM %s WHERE %s GROUP BY u.id ORDER BY %s;', $columns, $tables, $where, $df->getNameFieldsSQL('u')));

		CSV::export($format, $name, $i, $header, [self::class, 'exportRowCallback']);
	}

	static public function exportRowCallback(&$row)
	{
		$df = DynamicFields::getInstance();

		foreach ($row as $key => &$value) {
			$field = $df->get($key);

			if (!$field || null === $value) {
				continue;
			}

			if ($field->type === 'date' && is_string($value)) {
				$value = \DateTime::createFromFormat('!Y-m-d', $value);
			}
			elseif ($field->type === 'datetime' && is_string($value)) {
				$value = \DateTime::createFromFormat('!Y-m-d', $value);
			}
			else {
				$value = $field->getStringValue($value);
			}
		}

		unset($value);
	}
}
