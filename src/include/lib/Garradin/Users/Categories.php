<?php

namespace Garradin\Users;

use Garradin\DB;
use Garradin\Entities\Users\Category;
use KD2\DB\EntityManager as EM;

class Categories
{
	static public function get(int $id): ?Category
	{
		return EM::findOneById(Category::class, $id);
	}

	static public function listSimple(): array
	{
		return DB::getInstance()->getAssoc(sprintf('SELECT id, name FROM %s ORDER BY name COLLATE NOCASE;', Category::TABLE));
	}

	static public function listWithStats(): array
	{
		return DB::getInstance()->getGrouped(sprintf('SELECT c.id, c.*,
			(SELECT COUNT(*) FROM membres WHERE id_category = c.id) AS count
			FROM %s c ORDER BY c.name COLLATE NOCASE;', Category::TABLE));
	}

	static public function listHidden(): array
	{
		return DB::getInstance()->getAssoc(sprintf('SELECT id, name FROM %s WHERE hidden = 1
			ORDER BY name COLLATE NOCASE;', Category::TABLE));
	}

	static public function listNotHidden(): array
	{
		return DB::getInstance()->getAssoc(sprintf('SELECT id, name FROM %s WHERE hidden = 0
			ORDER BY name COLLATE NOCASE;', Category::TABLE));
	}
}
