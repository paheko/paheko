<?php

namespace Garradin;

use Garradin\Entities\Search as SE;

use KD2\DB\EntityManager as EM;

class Search
{
	static public function list(int $id_user, string $target): array
	{
		return EM::getInstance(SE::class)->all('SELECT * FROM @TABLE
			WHERE (id_user IS NULL OR id_user = ?) AND target = ?
			ORDER BY label COLLATE U_NOCASE;', $id_user, $target);
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
}
