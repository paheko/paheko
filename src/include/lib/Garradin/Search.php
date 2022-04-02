<?php

namespace Garradin;

use Garradin\Entities\Search as SE;

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
}
