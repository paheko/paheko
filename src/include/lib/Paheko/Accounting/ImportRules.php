<?php

namespace Paheko\Accounting;

use Paheko\Entities\Accounting\ImportRule;
use Paheko\DynamicList;

use KD2\DB\EntityManager as EM;

class ImportRules
{
	static public function getList(): DynamicList
	{
		$columns = [
			'id' => [],
			'label' => [
				'label' => 'Nom',
				'order' => 'label COLLATE U_NOCASE %s',
			],
			'match_file_name' => [
				'label' => 'Nom de fichier',
			],
			'match_label' => [
				'label' => 'Libellé écriture',
			],
			'match_account' => [
				'label' => 'Compte source',
			],
			'target_account' => [
				'label' => 'Compte destinataire',
			],
		];

		$list = new DynamicList($columns, ImportRule::TABLE);
		$list->orderBy('label', false);
		return $list;
	}

	static public function get(int $id): ?ImportRule
	{
		return EM::findOneById(ImportRule::class, $id);
	}

	static public function create(): ImportRule
	{
		return new ImportRule;
	}
}
