<?php

namespace Paheko\Users;

use Paheko\DB;
use Paheko\Entities\Users\Category;
use Paheko\Entities\Users\User;
use KD2\DB\EntityManager as EM;

class Categories
{
	const HIDDEN_ONLY = 1;
	const WITHOUT_HIDDEN = 0;

	static public function get(int $id): ?Category
	{
		return EM::findOneById(Category::class, $id);
	}

	static protected function getHiddenClause(?int $hidden = null): string
	{
		if (self::HIDDEN_ONLY === $hidden) {
			return 'AND hidden = 1';
		}
		elseif (self::WITHOUT_HIDDEN === $hidden) {
			return 'AND hidden = 0';
		}

		return '';
	}

	static public function listAssoc(?int $hidden = null): array
	{
		return DB::getInstance()->getAssoc(sprintf('SELECT id, name FROM %s WHERE 1 %s ORDER BY name COLLATE U_NOCASE;',
			Category::TABLE, self::getHiddenClause($hidden)
		));
	}

	/**
	 * Return a list of categories that have less or same permissions as the logged user
	 */
	static public function listAssocSafe(Session $session, ?int $hidden = null): array
	{
		$perms = $session->getPermissions();
		$conditions = self::getHiddenClause($hidden);

		foreach ($perms as $section => $level) {
			$conditions .= sprintf(' AND perm_%s <= %d', $section, $level);
		}

		$sql = sprintf('SELECT id, name FROM %s WHERE 1 %s ORDER BY name COLLATE U_NOCASE;',
			Category::TABLE,
			$conditions
		);

		return DB::getInstance()->getAssoc($sql);
	}

	static public function listAssocWithStats(?int $hidden = null): array
	{
		$db = DB::getInstance();

		$categories = [0 => (object) [
			'label' => 'Toutes, sauf cachées',
			'count' => $db->count(User::TABLE, 'id_category NOT IN (SELECT id FROM users_categories WHERE hidden = 1)'),
		]];

		if ($hidden !== self::WITHOUT_HIDDEN) {
			$categories[-1] = (object) [
				'label' => 'Toutes, même cachées',
				'count' => $db->count(User::TABLE),
			];
		}

		$format = '%s (%d membres)';

		return $categories + $db->getGrouped(sprintf(
			'SELECT id, name AS label, (SELECT COUNT(*) FROM %s WHERE %1$s.id_category = %s.id) AS count
			FROM %2$s
			WHERE 1 %s
			ORDER BY name COLLATE U_NOCASE;',
			User::TABLE,
			Category::TABLE,
			self::getHiddenClause($hidden)
		));
	}

	static public function listWithStats(?int $hidden = null): array
	{
		return DB::getInstance()->getGrouped(sprintf('SELECT c.id, c.*,
			(SELECT COUNT(*) FROM users WHERE id_category = c.id) AS count
			FROM %s c WHERE 1 %s ORDER BY c.name COLLATE U_NOCASE;',
			Category::TABLE, self::getHiddenClause($hidden)
		));
	}
}
