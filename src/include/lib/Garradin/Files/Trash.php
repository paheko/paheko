<?php

namespace Garradin\Files;

use Garradin\Entities\Files\File;
use Garradin\DynamicList;

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
		Files::syncVirtualTable(File::CONTEXT_TRASH, true);

		$columns = self::LIST_COLUMNS;

		$tables = Files::getVirtualTableName();

		$conditions = sprintf('type = %d', File::TYPE_FILE);

		$list = new DynamicList($columns, $tables, $conditions);
		$list->orderBy('modified', true);

		return $list;
	}

	static public function pruneEmptyDirectories(): void
	{
		$paths = [];

		foreach (Files::listRecursive(File::CONTEXT_TRASH, null, true) as $file) {
			if ($file->isDir()) {
				$paths[$file->path] = 0;
			}
			else {
				if (!isset($paths[$file->parent])) {
					$paths[$file->parent] = 0;
				}

				$paths[$file->parent]++;
			}
		}

		foreach ($paths as $path => $count) {
			if (!$count) {
				Files::get($path)->delete();
			}
		}
	}

	static public function clean(string $expiry = '-30 days'): void
	{
		$past = new \DateTime($expiry);

		foreach (Files::listRecursive(File::CONTEXT_TRASH, null, true) as $file) {
			if ($file->modified < $past) {
				$file->delete();
			}
		}
	}
}
