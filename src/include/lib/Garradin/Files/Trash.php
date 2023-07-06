<?php

namespace Garradin\Files;

use Garradin\Entities\Files\File;
use Garradin\DynamicList;

use KD2\DB\EntityManager as EM;

class Trash
{
	const LIST_COLUMNS = [
		'name' => [
			'label' => 'Fichier',
		],
		'parent' => [
			'label' => 'Chemin d\'origine',
			'select' => 'SUBSTR(parent, LENGTH(\'trash/\') + 1)',
		],
		'path' => [
		],
		'modified' => [
			'label' => 'Supprimé le',
		],
	];

	static public function list(): DynamicList
	{
		$columns = self::LIST_COLUMNS;

		$tables = File::TABLE;

		$conditions = sprintf('type = %d AND trash IS NOT NULL', File::TYPE_FILE);

		$list = new DynamicList($columns, $tables, $conditions);
		$list->orderBy('modified', true);

		return $list;
	}

	static public function clean(string $expiry = '-30 days'): void
	{
		$past = new \DateTime($expiry);
		$deleted = false;

		$list = EM::getInstance(File::class)->all('SELECT * FROM @TABLE WHERE trash IS NOT NULL AND trash < ?;', $past);

		foreach ($list as $file) {
			$file->delete();
			$deleted = true;
		}

		if ($deleted) {
			Files::pruneEmptyDirectories();
		}
	}
}
