<?php

namespace Garradin\Files;

use Garradin\Entities\Files\File;
use Garradin\DynamicList;

class Transactions
{
	const LIST_COLUMNS = [
		'id' => [
			'select' => 't.id',
			'label' => 'N°',
		],
		'label' => [
			'select' => 't.label',
			'label' => 'Libellé',
		],
		'date' => [
			'label' => 'Date',
			'select' => 't.date',
			'order' => 't.date %s, t.id %1$s',
		],
		'reference' => [
			'label' => 'Pièce comptable',
			'select' => 't.reference',
		],
		'year' => [
			'select' => 'y.label',
			'label' => 'Exercice',
			'order' => 'y.end_date %s, t.date %1$s, t.id %1$s',
		],
		'path' => [
		],
	];

	static public function list()
	{
		Files::syncVirtualTable(File::CONTEXT_TRANSACTION);

		$columns = self::LIST_COLUMNS;

		$tables = sprintf('%s f
			INNER JOIN acc_transactions t ON t.id = f.name
			INNER JOIN acc_years y ON t.id_year = y.id', Files::getVirtualTableName());

		$sum = 0;

		// Only fetch directories with an ID as the name
		$conditions = sprintf('f.parent = \'%s\' AND f.type = %d AND printf("%%d", f.name) = name', File::CONTEXT_TRANSACTION, File::TYPE_DIRECTORY);

		$list = new DynamicList($columns, $tables, $conditions);
		$list->orderBy('year', true);
		$list->setCount('COUNT(DISTINCT t.id)');
		$list->setModifier(function (&$row) {
			$row->date = \DateTime::createFromFormat('!Y-m-d', $row->date);
		});

		return $list;
	}
}