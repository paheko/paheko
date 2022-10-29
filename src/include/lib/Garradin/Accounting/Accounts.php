<?php

namespace Garradin\Accounting;

use Garradin\Entities\Accounting\Account;
use Garradin\Entities\Accounting\Line;
use Garradin\Entities\Accounting\Transaction;
use Garradin\Entities\Accounting\Year;
use Garradin\Config;
use Garradin\CSV;
use Garradin\DB;
use Garradin\DynamicList;
use Garradin\Utils;
use Garradin\UserException;
use Garradin\ValidationException;
use KD2\DB\EntityManager;

class Accounts
{
	protected $chart_id;
	protected $em;

	const EXPECTED_CSV_COLUMNS = ['code', 'label', 'description', 'position', 'type'];

	public function __construct(int $chart_id)
	{
		$this->chart_id = $chart_id;
		$this->em = EntityManager::getInstance(Account::class);
	}

	static public function get(int $id)
	{
		return EntityManager::findOneById(Account::class, $id);
	}

	static public function getSelector(?int $id): ?array
	{
		if (!$id) {
			return null;
		}

		return [$id => self::getCodeAndLabel($id)];
	}

	static public function getCodeAndLabel(int $id): string
	{
		return EntityManager::getInstance(Account::class)->col('SELECT code || \' — \' || label FROM @TABLE WHERE id = ?;', $id);
	}

	public function getIdFromCode(string $code): int
	{
		return $this->em->col('SELECT id FROM @TABLE WHERE code = ? AND id_chart = ?;', $code, $this->chart_id);
	}

	static public function getCodeFromId(string $id): string
	{
		return EntityManager::getInstance(Account::class)->col('SELECT code FROM @TABLE WHERE id = ?;', $id);
	}

	/**
	 * Return common accounting accounts from current chart
	 * (will not return analytical and volunteering accounts)
	 */
	public function listCommonTypes(): array
	{
		return $this->em->all('SELECT * FROM @TABLE WHERE id_chart = ? AND type != 0 AND type != ORDER BY code COLLATE U_NOCASE;',
			$this->chart_id, Account::TYPE_VOLUNTEERING);
	}

	/**
	 * Return all accounts from current chart
	 */
	public function listAll(?array $targets = null): array
	{
		$where = '';

		if (!empty($targets)) {
			$position = null;

			if (in_array(Account::TYPE_EXPENSE, $targets)) {
				$position = Account::EXPENSE;
			}
			elseif (in_array(Account::TYPE_REVENUE, $targets)) {
				$position = Account::REVENUE;
			}

			if ($position) {
				$where = sprintf('AND position = %d', $position);
			}
		}

		return $this->em->all(sprintf('SELECT * FROM @TABLE WHERE id_chart = ? %s ORDER BY code COLLATE U_NOCASE;', $where),
			$this->chart_id);
	}

	public function listForCodes(array $codes): array
	{
		return DB::getInstance()->getGrouped('SELECT code, id, label FROM acc_accounts WHERE id_chart = ?;', $this->chart_id);
	}

	/**
	 * Return all accounts from current chart
	 */
	public function export(): \Generator
	{
		$res = $this->em->DB()->iterate($this->em->formatQuery('SELECT
			code, label, description, position, type, user AS added
			FROM @TABLE WHERE id_chart = ? ORDER BY code COLLATE U_NOCASE;'),
			$this->chart_id);

		foreach ($res as $row) {
			$row->type = Account::TYPES_NAMES[$row->type];
			$row->position = Account::POSITIONS_NAMES[$row->position];
			$row->added = $row->added ? 'Ajouté' : '';
			yield $row;
		}
	}

	public function listVolunteering(): array
	{
		return $this->em->all('SELECT * FROM @TABLE WHERE id_chart = ? AND type = ? ORDER BY code COLLATE U_NOCASE;',
			$this->chart_id, Account::TYPE_VOLUNTEERING);
	}

	/**
	 * List common accounts, grouped by type
	 * @return array
	 */
	public function listCommonGrouped(array $types = null, bool $include_empty_types = false): array
	{
		if (null === $types) {
			$types = '';
		}
		else {
			$types = array_map('intval', $types);
			$types = ' AND ' . $this->em->DB()->where('type', $types);
		}

		$out = [];

		if ($include_empty_types) {
			foreach (Account::TYPES_NAMES as $key => $label) {
				if (!$label) {
					continue;
				}

				$out[$key] = (object) [
					'label'    => $label,
					'type'     => $key,
					'accounts' => [],
				];
			}
		}

		$query = $this->em->iterate('SELECT * FROM @TABLE WHERE id_chart = ? AND type != 0 ' . $types . ' ORDER BY type, code COLLATE U_NOCASE;',
			$this->chart_id);

		foreach ($query as $row) {
			if (!isset($out[$row->type])) {
				$out[$row->type] = (object) [
					'label'    => Account::TYPES_NAMES[$row->type],
					'type'     => $row->type,
					'accounts' => [],
				];
			}

			$out[$row->type]->accounts[] = $row;
		}

		return $out;
	}

	public function getNextCodeForType(int $type): string
	{
		$db = DB::getInstance();
		$used_codes = $db->getAssoc(sprintf('SELECT code, code FROM %s WHERE type = ? AND user = 1 AND id_chart = ?;', Account::TABLE), $this->chart_id, $type);
		$used_codes = array_values($used_codes);

		$sql = sprintf('SELECT type, MIN(code) AS code, (SELECT COUNT(*) FROM %s WHERE user = 1 AND type = a.type) AS count
			FROM %1$s AS a
			WHERE id_chart = ? AND type = ?
			GROUP BY type;', Account::TABLE);
		$r = $db->first($sql, $this->chart_id, $type);

		if (!$r) {
			return '';
		}

		$code = preg_replace('/[^\d]/', '', $r->code);

		$count = $r->count;
		$found = null;

		// Make sure we don't reuse an existing code
		while (!$found || in_array($found, $used_codes)) {
			// Get new account code, eg. 512A, 99AA, 99BZ etc.
			$letter = Utils::num2alpha($count++);
			$found = $code . $letter;
		}

		return $found;
	}

	static public function getPositionFromType(int $type): int
	{
		switch ($type) {
			case Account::TYPE_REVENUE;
				return Account::REVENUE;
			case Account::TYPE_EXPENSE;
				return Account::EXPENSE;
			case Account::TYPE_VOLUNTEERING:
				return Account::NONE;
			default:
				return Account::ASSET_OR_LIABILITY;
		}
	}

	public function copyFrom(int $id)
	{
		$db = DB::getInstance();
		return $db->exec(sprintf('INSERT INTO %s (id_chart, code, label, description, position, type, user)
			SELECT %d, code, label, description, position, type, user FROM %1$s WHERE id_chart = %d;', Account::TABLE, $this->chart_id, $id));
	}

	public function importUpload(array $file)
	{
		if (empty($file['size']) || empty($file['tmp_name'])) {
			throw new UserException('Fichier invalide');
		}

		self::importCSV($file['tmp_name']);
	}

	public function importCSV(string $file, bool $update = false): void
	{
		$db = DB::getInstance();
		$positions = array_flip(Account::POSITIONS_NAMES);
		$types = array_flip(Account::TYPES_NAMES);

		$db->begin();

		try {
			foreach (CSV::import($file, self::EXPECTED_CSV_COLUMNS) as $line => $row) {
				$account = null;

				if ($update) {
					$account = EntityManager::findOne(Account::class, 'SELECT * FROM @TABLE WHERE code = ? AND id_chart = ?;', $row['code'], $this->chart_id);
				}

				if (!$account) {
					$account = new Account;
					$account->id_chart = $this->chart_id;
				}

				try {
					if (!isset($positions[$row['position']])) {
						throw new ValidationException('Position inconnue : ' . $row['position']);
					}

					if (!isset($types[$row['type']])) {
						throw new ValidationException('Type inconnu : ' . $row['type']);
					}

					// Don't update user-set values
					if ($account->exists()) {
						unset($row['type'], $row['description']);
					}
					else {
						$row['type'] = $types[$row['type']];
						$row['user'] = empty($row['added']) ? 0 : 1;
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

	public function countByType(int $type)
	{
		return DB::getInstance()->count(Account::TABLE, 'id_chart = ? AND type = ?', $this->chart_id, $type);
	}

	public function getSingleAccountForType(int $type)
	{
		return DB::getInstance()->first('SELECT * FROM acc_accounts WHERE type = ? AND id_chart = ? LIMIT 1;', $type, $this->chart_id);
	}

	public function getOpeningAccountId(): ?int
	{
		return DB::getInstance()->firstColumn('SELECT id FROM acc_accounts WHERE type = ? AND id_chart = ?;', Account::TYPE_OPENING, $this->chart_id) ?: null;
	}

	public function getClosingAccountId()
	{
		return DB::getInstance()->firstColumn('SELECT id FROM acc_accounts WHERE type = ? AND id_chart = ?;', Account::TYPE_CLOSING, $this->chart_id);
	}

	public function listUserAccounts(int $year_id): DynamicList
	{
		$id_field = Config::getInstance()->champ_identite;

		$columns = [
			'id' => [
				'select' => 'u.id',
			],
			'user_number' => [
				'select' => 'u.numero',
				'label' => 'N° membre',
			],
			'user_identity' => [
				'select' => 'u.' . $id_field,
				'label' => 'Membre',
			],
			'balance' => [
				'select' => 'SUM(l.debit - l.credit)',
				'label'  => 'Solde',
				//'order'  => 'balance != 0 %s, balance < 0 %1$s',
			],
			'status' => [
				'select' => null,
				'label' => 'Statut',
			],
		];

		$tables = 'acc_transactions_users tu
			INNER JOIN membres u ON u.id = tu.id_user
			INNER JOIN acc_transactions t ON tu.id_transaction = t.id
			INNER JOIN acc_transactions_lines l ON t.id = l.id_transaction
			INNER JOIN acc_accounts a ON a.id = l.id_account';

		$conditions = 'a.type = ' . Account::TYPE_THIRD_PARTY . ' AND t.id_year = ' . $year_id;

		$list = new DynamicList($columns, $tables, $conditions);
		$list->orderBy('balance', false);
		$list->groupBy('u.id');
		$list->setCount('COUNT(*)');
		$list->setPageSize(null);
		$list->setExportCallback(function (&$row) {
			$row->balance = Utils::money_format($row->balance, '.', '', false);
		});

		return $list;
	}

/* FIXME: implement closing of accounts

	public function closeRevenueExpenseAccounts(Year $year, int $user_id)
	{
		$closing_id = $this->getClosingAccountId();

		if (!$closing_id) {
			throw new UserException('Aucun compte n\'est indiqué comme compte de clôture dans le plan comptable');
		}

		$transaction = new Transaction;
		$transaction->id_creator = $user_id;
		$transaction->id_year = $year->id();
		$transaction->type = Transaction::TYPE_ADVANCED;
		$transaction->label = 'Clôture de l\'exercice';
		$transaction->date = new \DateTime;
		$debit = 0;
		$credit = 0;

		$sql = 'SELECT a.id, SUM(l.credit - l.debit) AS sum, a.position, a.code
			FROM acc_transactions_lines l
			INNER JOIN acc_transactions t ON t.id = l.id_transaction
			INNER JOIN acc_accounts a ON a.id = l.id_account
			WHERE t.id_year = ? AND a.position IN (?, ?)
			GROUP BY a.id
			ORDER BY a.code;';

		$res = DB::getInstance()->iterate($sql, $year->id(), Account::REVENUE, Account::EXPENSE);

		foreach ($res as $row) {
			$reversed = $row->position == Account::ASSET;

			$line = new Line;
			$line->id_account = $row->id;
			$line->credit = $reversed ? abs($row->sum) : 0;
			$line->debit = !$reversed ? abs($row->sum) : 0;
			$transaction->addLine($line);

			if ($reversed) {
				$debit += abs($row->sum);
			}
			else {
				$credit += abs($row->sum);
			}
		}

		if ($debit) {
			$line = new Line;
			$line->id_account = $closing_id;
			$line->credit = 0;
			$line->debit = $debit;
			$transaction->addLine($line);
		}

		if ($credit) {
			$line = new Line;
			$line->id_account = $closing_id;
			$line->credit = $credit;
			$line->debit = 0;
			$transaction->addLine($line);
		}

		$transaction->save();
	}
*/
}