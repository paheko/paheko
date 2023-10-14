<?php

namespace Paheko\Entities\Accounting;

use Paheko\CSV;
use Paheko\DB;
use Paheko\Entity;
use Paheko\Utils;
use Paheko\ValidationException;
use Paheko\UserException;
use Paheko\Accounting\Accounts;

use KD2\DB\EntityManager;

class Chart extends Entity
{
	const NAME = 'Plan comptable';
	const PRIVATE_URL = '!acc/charts/accounts/all.php?id=%d';

	const TABLE = 'acc_charts';

	protected ?int $id;
	protected string $label;
	protected ?string $country = null;
	protected ?string $code;
	protected bool $archived = false;

	const COUNTRY_LIST = [
		'BE' => 'Belgique',
		'FR' => 'France',
		'CH' => 'Suisse',
	];

	const REQUIRED_COLUMNS = ['code', 'label', 'description', 'position', 'bookmark'];

	const COLUMNS = [
		'code' => 'Numéro',
		'label' => 'Libellé',
		'description' => 'Description',
		'position' => 'Position',
		'added' => 'Ajouté',
		'bookmark' => 'Favori',
	];

	public function selfCheck(): void
	{
		$this->assert(trim($this->label) !== '', 'Le libellé ne peut rester vide.');
		$this->assert(strlen($this->label) <= 200, 'Le libellé ne peut faire plus de 200 caractères.');
		$this->assert(null === $this->country || array_key_exists($this->country, self::COUNTRY_LIST), 'Pays inconnu');
		parent::selfCheck();
	}

	public function accounts()
	{
		return new Accounts($this->id());
	}

	public function importForm(array $source = null)
	{
		if (null === $source) {
			$source = $_POST;
		}

		// Don't allow to change country
		if (isset($this->code)) {
			unset($source['country']);
		}

		unset($source['code']);

		return Entity::importForm($source);
	}

	public function canDelete()
	{
		return !DB::getInstance()->firstColumn(sprintf('SELECT 1 FROM %s WHERE id_chart = ? LIMIT 1;', Year::TABLE), $this->id());
	}

	public function importCSV(string $file, bool $update = false): void
	{
		$db = DB::getInstance();
		$positions = array_flip(Account::POSITIONS_NAMES);
		$types = array_flip(Account::TYPES_NAMES);

		$db->begin();

		try {
			foreach (CSV::import($file, self::COLUMNS, self::REQUIRED_COLUMNS) as $line => $row) {
				$account = null;

				if ($update) {
					$account = EntityManager::findOne(Account::class, 'SELECT * FROM @TABLE WHERE code = ? AND id_chart = ?;', $row['code'], $this->id());
				}

				if (!$account) {
					$account = new Account;
					$account->id_chart = $this->id();
				}

				try {
					if (!isset($positions[$row['position']])) {
						throw new ValidationException('Position inconnue : ' . $row['position']);
					}
					// Don't update user-set values
					if ($account->exists()) {
						unset($row['bookmark'], $row['description']);
					}
					else {
						$row['user'] = !empty($row['added']);
						$row['bookmark'] = !empty($row['bookmark']);
					}

					$row['position'] = $positions[$row['position']];

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


	/**
	 * Return all accounts from current chart
	 */
	public function export(): \Generator
	{
		$res = DB::getInstance()->iterate('SELECT
			code, label, description, position, user, bookmark
			FROM acc_accounts WHERE id_chart = ? ORDER BY code COLLATE NOCASE;',
			$this->id);

		foreach ($res as $row) {
			$row->position = Account::POSITIONS_NAMES[$row->position];
			$row->user = $row->user ? 'Ajouté' : '';
			$row->bookmark = $row->bookmark ? 'Favori' : '';
			yield $row;
		}
	}

	public function resetAccountsRules(): void
	{
		$db = DB::getInstance();
		$db->begin();

		try {
			foreach ($this->accounts()->listAll() as $account) {
				$account->setLocalRules($this->country);
				$account->save();
			}
		}
		catch (UserException $e) {
			$db->rollback();
			throw $e;
		}

		$db->commit();
	}

	public function save(bool $selfcheck = true): bool
	{
		$country_modified = $this->isModified('country');
		$exists = $this->exists();

		$ok = parent::save($selfcheck);

		// Change account types
		if ($ok && $exists && $country_modified) {
			$this->resetAccountsRules();
		}

		return $ok;
	}

	public function country_code(): ?string
	{
		if (!$this->code) {
			return null;
		}

		return strtolower($this->country . '_' . $this->code);
	}
}
