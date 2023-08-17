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
			'select' => '\'user/\' || u.id',
		],
		'id' => [
			'label' => null,
			'select' => 'u.id',
		],
	];

	static public function list(): DynamicList
	{
		Files::pruneEmptyDirectories(File::CONTEXT_USER);

		$columns = self::LIST_COLUMNS;
		$columns['identity']['select'] = DF::getNameFieldsSQL('u');
		$columns['identity']['label'] = DF::getNameLabel();
		$columns['number']['select'] = DF::getNumberField();

		$tables = 'users_files uf INNER JOIN users u ON uf.id_user = u.id INNER JOIN files f ON uf.id_file = f.id AND f.trash IS NULL';

		$list = new DynamicList($columns, $tables);
		$list->orderBy('number', false);
		$list->groupBy('uf.id_user');
		$list->setCount('COUNT(DISTINCT uf.id_user)');

		return $list;
	}
}