<?php

namespace Garradin\Entities\Accounting;

use Garradin\DB;
use Garradin\Entity;
use Garradin\Utils;
use Garradin\ValidationException;
use Garradin\UserException;
use Garradin\Accounting\Accounts;

class Chart extends Entity
{
	const TABLE = 'acc_charts';

	protected $id;
	protected $label;
	protected $country;
	protected $code;
	protected $archived = 0;

	protected $_types = [
		'id'       => 'int',
		'label'    => 'string',
		'country'  => 'string',
		'code'     => '?string',
		'archived' => 'int',
	];

	protected $_form_rules = [
		'label'    => 'required|string|max:200',
		'country'  => 'required|string|size:2',
		'archived' => 'numeric|min:0|max:1'
	];

	const EXPECTED_CSV_COLUMNS = ['code', 'label', 'description', 'position', 'type'];

	public function selfCheck(): void
	{
		parent::selfCheck();
		$this->assert(Utils::getCountryName($this->country), 'Le code pays doit être un code ISO valide');
		$this->assert($this->archived === 0 || $this->archived === 1);
	}

	public function accounts()
	{
		return new Accounts($this->id());
	}

	public function canDelete()
	{
		return !DB::getInstance()->firstColumn(sprintf('SELECT 1 FROM %s WHERE id_chart = ? LIMIT 1;', Year::TABLE), $this->id());
	}

	public function importCSV(array $file): void
	{
		$db = DB::getInstance();
		$positions = array_flip(Account::POSITIONS_NAMES);
		$types = array_flip(Account::TYPES_NAMES);

		$db->begin();
		$this->save();

		try {
			foreach (Utils::fromCSV($file, self::EXPECTED_CSV_COLUMNS) as $line => $row) {
				$account = new Account;
				$account->id_chart = $this->id();
				try {
					$row['position'] = $positions[$row['position']];
					$row['type'] = $types[$row['type']];
					$account->importForm($row);
					$account->save();
				}
				catch (ValidationException $e) {
					throw new UserException(sprintf('Ligne %d : %s', $line, $e->getMessage()));
				}
			}

			$db->commit();
		}
		catch (\Exception $e) {
			$db->rollback();
			throw $e;
		}
	}
}
