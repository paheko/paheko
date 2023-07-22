<?php

namespace Paheko\Payments;

use Paheko\Entities\Payments\Provider;
use Paheko\DynamicList;
use KD2\DB\EntityManager;

class Providers
{
	const MANUAL_PROVIDER = 'manual';
	const MANUAL_PROVIDER_LABEL = 'Saisie manuelle';

	static ?Provider $manualProvider = null;

	static public function getAll(): array
	{
		$providers = EntityManager::getInstance(Provider::class)->all('SELECT * FROM @TABLE');
		$providers[] = self::getManualProviderInstance();
		return $providers;
	}
	
	static public function getManualProviderInstance(): Provider
	{
		if (null === self::$manualProvider)
			self::$manualProvider = self::_createManualProvider();
		return self::$manualProvider;
	}
	
	static protected function _createManualProvider(): Provider
	{
		$manual = new Provider();
		$manual->name = self::MANUAL_PROVIDER;
		$manual->label = self::MANUAL_PROVIDER_LABEL;
		return $manual;
	}
	
	static function getByName(string $name): ?Provider
	{
		if ($name === self::MANUAL_PROVIDER) {
			return self::getManualProviderInstance();
		}
		return EntityManager::findOne(Provider::class, 'SELECT * FROM @TABLE WHERE name = ?;', $name);
	}

	static public function list(): DynamicList
	{
		$columns = [
			'id' => [
				'select' => 'id',
				'label' => 'ID'
			],
			'name' => [
				'label' => 'Nom',
				'select' => 'name'
			],
			'label' => [
				'label' => 'Libellé',
				'select' => 'label'
			]
		];

		$list = new DynamicList($columns, Provider::TABLE);

		$list->orderBy('label', true);
		return $list;
	}
}
