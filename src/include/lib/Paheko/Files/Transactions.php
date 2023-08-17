<?php

namespace Paheko\Files;

use Paheko\Entities\Files\File;
use Paheko\DynamicList;

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
			'select' => '\'transaction/\' || t.id',
		],
	];

	static public function list()
	{
		Files::pruneEmptyDirectories(File::CONTEXT_TRANSACTION);

		$columns = self::LIST_COLUMNS;

		$tables = 'acc_transactions_files tf
			INNER JOIN acc_transactions t ON t.id = tf.id_transaction
			INNER JOIN acc_years y ON t.id_year = y.id
			INNER JOIN files f ON f.id = tf.id_file AND f.trash IS NULL';

		$list = new DynamicList($columns, $tables);
		$list->orderBy('id', true);
		$list->groupBy('t.id');
		$list->setCount('COUNT(DISTINCT tf.id_transaction)');
		$list->setModifier(function (&$row) {
			$row->date = \DateTime::createFromFormat('!Y-m-d', $row->date);
		});

		return $list;
	}
}