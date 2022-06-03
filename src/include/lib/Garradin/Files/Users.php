<?php

namespace Garradin\Files;

use Garradin\Entities\Files\File;
use Garradin\DynamicList;
use Garradin\Users\DynamicFields as DF;

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
		],
		'id' => [
			'label' => null,
			'select' => 'm.id',
		],
	];

	static public function list(): DynamicList
	{
		Files::syncVirtualTable(File::CONTEXT_USER);

		$columns = self::LIST_COLUMNS;
		$columns['identity']['select'] = DF::getNameFieldsSQL('u');
		$columns['identity']['label'] = DF::getNameLabel();
		$columns['number']['select'] = DF::getNumberField();

		$tables = sprintf('%s f INNER JOIN users u ON u.id = f.name', Files::getVirtualTableName());

		$sum = 0;

		// Only fetch directories with an ID as the name
		$conditions = sprintf('f.parent = \'%s\' AND f.type = %d AND printf("%%d", f.name) = name', File::CONTEXT_USER, File::TYPE_DIRECTORY);

		$list = new DynamicList($columns, $tables, $conditions);
		$list->orderBy('number', false);
		$list->setCount('COUNT(DISTINCT m.id)');

		return $list;
	}
}