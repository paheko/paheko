<?php

namespace Paheko\Files;

use Paheko\Entities\Files\File;
use Paheko\DynamicList;
use Paheko\Users\DynamicFields as DF;

class Users
{
	const LIST_COLUMNS = [
		'number' => [
			'label' => 'Numéro',
		],
		'identity' => [
			'select' => '',
			'label' => '',
		],
		'path' => [
			'select' => 'parent',
		],
		'id' => [
			'label' => null,
			'select' => 'u.id',
		],
	];

	static public function list(): DynamicList
	{
		Files::pruneEmptyDirectories();

		$columns = self::LIST_COLUMNS;
		$columns['identity']['select'] = DF::getNameFieldsSQL('u');
		$columns['identity']['label'] = DF::getNameLabel();
		$columns['number']['select'] = DF::getNumberField();

		$tables = sprintf('%s f INNER JOIN users u ON f.parent = \'%s/\' || u.id', File::TABLE, File::CONTEXT_USER);

		$list = new DynamicList($columns, $tables);
		$list->orderBy('number', false);
		$list->groupBy('u.id');
		$list->setCount('COUNT(DISTINCT u.id)');

		return $list;
	}
}