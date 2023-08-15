<?php

namespace Paheko\Files;

use Paheko\Entities\Files\File;
use Paheko\DB;
use Paheko\DynamicList;

use KD2\DB\EntityManager as EM;

class Trash
{
	const LIST_COLUMNS = [
		'name' => [
			'label' => 'Fichier',
		],
		'parent' => [
			'label' => 'Chemin d\'origine',
			'select' => 'SUBSTR(parent, 1 + LENGTH(\'trash/\'))',
		],
		'path' => [
		],
		'trash' => [
			'label' => 'Supprimé le',
		],
	];

	static public function list(): DynamicList
	{
		$columns = self::LIST_COLUMNS;

		$tables = File::TABLE;

		$conditions = 'trash IS NOT NULL';

		$list = new DynamicList($columns, $tables, $conditions);
		$list->orderBy('trash', true);

		return $list;
	}

	static public function clean(string $expiry = '-30 days'): void
	{
		$past = new \DateTime($expiry);
		$list = EM::getInstance(File::class)->all('SELECT * FROM @TABLE WHERE trash IS NOT NULL AND trash < ?;', $past);

		foreach ($list as $file) {
			$file->delete();
		}
	}

	static public function getSize(): int
	{
		$db = DB::getInstance();
		return $db->firstColumn('SELECT SUM(size) FROM files WHERE trash IS NOT NULL;') ?: 0;
	}

}
