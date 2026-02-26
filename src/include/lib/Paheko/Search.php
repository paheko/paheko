<?php

namespace Paheko;

use Paheko\Entities\Search as SE;
use Paheko\DynamicList;

use KD2\DB\EntityManager as EM;

class Search
{
	static public function getList(string $target, ?int $id_user): DynamicList
	{
		$columns = [
			'label' => [
				'label' => 'Recherche',
				'order' => 'label COLLATE U_NOCASE %s',
			],
			'type' => [
				'label' => 'Type',
				'order' => 'type %s, label COLLATE U_NOCASE %1$s',
			],
			'id_user' => [
				'label' => 'Statut',
				'order' => 'id_user %s, label COLLATE U_NOCASE %1$s',
			],
			'updated' => [
				'label' => 'Mise à jour',
			],
			'id' => [],
		];

		$tables = SE::TABLE;

		$conditions = 'target = :target AND (id_user IS NULL%s)';
		$conditions = sprintf($conditions, $id_user ? sprintf(' OR id_user = %d', $id_user): '');

		$list = new DynamicList($columns, $tables, $conditions);
		$list->setParameter('target', $target);
		$list->orderBy('label', false);
		$list->setModifier(function (&$row) {
			$row->type = $row->type === SE::TYPE_JSON ? 'Avancée' : 'SQL';
		});

		return $list;
	}

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

	static public function create(string $target, ?string $type = null): SE
	{
		$s = new SE;
		$s->set('target', $target);

		if ($type !== null) {
			$s->set('type', $type);
			$label = $s->type != $s::TYPE_JSON ? 'Recherche SQL du ' : 'Recherche avancée du ';
			$label .= date('d/m/Y à H:i');
			$s->set('label', $label);
		}

		return $s;
	}

	static public function simple(string $target, string $query): SE
	{
		$s = new SE;
		$s->target = $target;
		$s->simple($query);
		return $s;
	}

	static public function simpleList(string $target, string $query): DynamicList
	{
		return self::simple($target, $query)->getDynamicList();
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
