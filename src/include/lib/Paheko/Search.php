<?php

namespace Paheko;

use Paheko\Entities\Search as SE;

use KD2\DB\EntityManager as EM;

class Search
{
	static public function list(string $target, ?int $id_user): array
	{
		$params = [$target];
		$where = '';

		if ($id_user) {
			$where = ' OR id_user = ?';
			$params[] = $id_user;
		}

		$sql = sprintf('SELECT * FROM @TABLE
			WHERE target = ? AND (id_user IS NULL%s)
			ORDER BY label COLLATE U_NOCASE;', $where);

		return EM::getInstance(SE::class)->all($sql, ...$params);
	}

	static public function listAssoc(string $target, ?int $id_user): array
	{
		$out = [];

		foreach (self::list($target, $id_user) as $row) {
			$out[$row->id] = $row->label;
		}

		return $out;
	}

	static public function get(int $id): ?SE
	{
		return EM::findOneById(SE::class, $id);
	}

	static public function quick(string $target, string $query): DynamicList
	{
		$s = new SE;
		$s->target = $target;
		return $s->quick($query);
	}

	static public function fromSQL(string $sql): SE
	{
		$s = new SE;
		$s->type = $s::TYPE_SQL;
		$s->content = $sql;
		$s->target = $s::TARGET_ALL;
		return $s;
	}
}
