<?php

namespace Paheko\Accounting;

use Paheko\Entities\Accounting\Account;
use Paheko\Entities\Accounting\Line;
use Paheko\Entities\Accounting\Transaction;
use Paheko\Entities\Accounting\Year;
use Paheko\Users\DynamicFields;
use Paheko\DB;
use Paheko\DynamicList;
use Paheko\Utils;
use Paheko\UserException;
use Paheko\ValidationException;
use KD2\DB\EntityManager;

class Accounts
{
	protected $chart_id;
	protected $em;

	public function __construct(int $chart_id)
	{
		$this->chart_id = $chart_id;
		$this->em = EntityManager::getInstance(Account::class);
	}

	static public function get(int $id)
	{
		return EntityManager::findOneById(Account::class, $id);
	}

	public function getWithCode(string $code): ?Account
	{
		return EntityManager::findOne(Account::class, 'SELECT * FROM @TABLE WHERE code = ? AND id_chart = ?', $code, $this->chart_id);
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

	public function getSelectorFromCode(?string $code): ?array
	{
		if (!$code) {
			return null;
		}

		$a = DB::getInstance()->first(
			'SELECT id, code || \' — \' || label AS label FROM acc_accounts WHERE code = ? AND id_chart = ?;',
			$code,
			$this->chart_id);

		if (!$a) {
			return null;
		}

		return [$a->id => $a->label];
	}

	/**
	 * Return common accounting accounts from current chart
	 * (will not return analytical and volunteering accounts)
	 */
	public function listCommonTypes(): array
	{
		$sql = sprintf('SELECT * FROM @TABLE WHERE id_chart = %d AND %s ORDER BY code COLLATE NOCASE;',
			$this->chart_id,
			DB::getInstance()->where('type', Account::COMMON_TYPES)
		);
		return $this->em->all($sql);
	}

	public function list(?array $types = null): DynamicList
	{
		$columns = [
			'id' => [
			],
			'code' => [
				'label' => 'N°',
				'order' => 'code COLLATE NOCASE %s',
			],
			'label' => [
				'label' => 'Libellé',
			],
			'description' => [
				'label' => '',
				'order' => null,
			],
			'level' => [
				'select' => 'CASE WHEN LENGTH(code) >= 6 THEN 6 ELSE LENGTH(code) END',
			],
			'report' => [
				'label' => ' ',
				'select' => null,
			],
			'position' => [
				'label' => 'Position',
			],
			'user' => [
				'label' => 'Ajouté',
			],
			'bookmark' => [
				'label' => 'Favori',
			],
		];

		$tables = 'acc_accounts';
		$conditions = 'id_chart = ' . $this->chart_id;

		if (!empty($types)) {
			$types = array_map('intval', $types);
			$conditions .= ' AND ' . DB::getInstance()->where('type', $types);
		}

		$list = new DynamicList($columns, $tables, $conditions);
		$list->orderBy('code', false);
		$list->setPageSize(null);
		$list->setModifier(function (&$row) {
			$row->position_report = !$row->position ? '' : ($row->position <= Account::ASSET_OR_LIABILITY ? 'Bilan' : 'Résultat');
			$row->position_name = Account::POSITIONS_NAMES[$row->position];
		});

		return $list;
	}

	public function listAll(array $types = null): array
	{
		$condition = '';

		if (!empty($types)) {
			$types = array_map('intval', $types);
			$condition = ' AND ' . DB::getInstance()->where('type', $types);
		}

		$sql = sprintf('SELECT * FROM @TABLE WHERE id_chart = %d %s ORDER BY code COLLATE NOCASE;', $this->chart_id, $condition);
		return $this->em->all($sql);
	}

	public function listForCodes(array $codes): array
	{
		return DB::getInstance()->getGrouped('SELECT code, id, label FROM acc_accounts WHERE id_chart = ?;', $this->chart_id);
	}

	/**
	 * List common accounts, grouped by type
	 * @return array
	 */
	public function listCommonGrouped(array $types = null, bool $hide_empty = false): array
	{
		if (null === $types) {
			// If we want all types, then we will get used or bookmarked accounts in common types
			// and only bookmarked accounts for other types, grouped in "Others"
			$target = Account::COMMON_TYPES;
		}
		else {
			$target = $types;
		}

		$out = [];

		foreach ($target as $type) {
			$out[$type] = (object) [
				'label'    => Account::TYPES_NAMES[$type],
				'type'     => $type,
				'accounts' => [],
			];
		}

		if (null === $types) {
			$out[0] = (object) [
				'label'    => 'Autres',
				'type'     => 0,
				'accounts' => [],
			];
		}

		$db = $this->em->DB();

		$sql = sprintf('SELECT a.* FROM @TABLE a
			LEFT JOIN acc_transactions_lines b ON b.id_account = a.id
			WHERE a.id_chart = %d AND ((a.%s AND (a.bookmark = 1 OR b.id IS NOT NULL)) %s)
			GROUP BY a.id
			ORDER BY type, code COLLATE NOCASE;',
			$this->chart_id,
			$db->where('type', $target),
			(null === $types) ? 'OR (a.bookmark = 1)' : ''
		);

		$query = $this->em->iterate($sql);

		foreach ($query as $row) {
			$t = in_array($row->type, $target) ? $row->type : 0;
			$out[$t]->accounts[] = $row;
		}

		if ($hide_empty) {
			foreach ($out as $key => $v) {
				if (!count($v->accounts)) {
					unset($out[$key]);
				}
			}
		}

		return $out;
	}

	/**
	 * List accounts from this type that are missing in current "usual" accounts list
	 */
	public function listMissing(int $type): array
	{
		if ($type != Account::TYPE_EXPENSE && $type != Account::TYPE_REVENUE && $type != Account::TYPE_THIRD_PARTY) {
			return [];
		}

		return $this->em->DB()->get($this->em->formatQuery('SELECT a.*, CASE WHEN LENGTH(a.code) >= 6 THEN 6 ELSE LENGTH(a.code) END AS level,
			(a.bookmark = 1 OR a.user = 1 OR b.id IS NOT NULL) AS already_listed
			FROM @TABLE a
			LEFT JOIN acc_transactions_lines b ON b.id_account = a.id
			WHERE a.id_chart = ? AND a.type = ?
			GROUP BY a.id
			ORDER BY type, code COLLATE NOCASE;'), $this->chart_id, $type);
	}

	public function countByType(int $type): int
	{
		return DB::getInstance()->count(Account::TABLE, 'id_chart = ? AND type = ?', $this->chart_id, $type);
	}

	public function getSingleAccountForType(int $type): ?Account
	{
		return $this->em->one('SELECT * FROM @TABLE WHERE type = ? AND id_chart = ? LIMIT 1;', $type, $this->chart_id);
	}

	public function getIdForType(int $type): ?int
	{
		return DB::getInstance()->firstColumn('SELECT id FROM acc_accounts WHERE type = ? AND id_chart = ? LIMIT 1;', $type, $this->chart_id);
	}

	public function getOpeningAccountId(): ?int
	{
		return $this->getIdForType(Account::TYPE_OPENING);
	}

	public function getClosingAccountId(): ?int
	{
		return $this->getIdForType(Account::TYPE_CLOSING);
	}

	public function listUserAccounts(int $year_id): DynamicList
	{
		$columns = [
			'id' => [
				'select' => 'u.id',
			],
			'user_number' => [
				'select' => 'u.' . DynamicFields::getNumberField(),
				'label' => 'N° membre',
			],
			'user_identity' => [
				'select' => DynamicFields::getNameFieldsSQL('u'),
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
			INNER JOIN users u ON u.id = tu.id_user
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

	/**
	 * Renvoie TRUE si le solde du compte est inversé (= crédit - débit, au lieu de débit - crédit)
	 * @return boolean
	 */
	static public function isReversed(bool $simple, int $type): bool
	{
		if ($simple && in_array($type, [Account::TYPE_BANK, Account::TYPE_CASH, Account::TYPE_OUTSTANDING, Account::TYPE_EXPENSE, Account::TYPE_THIRD_PARTY])) {
			return false;
		}

		return true;
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
		$transaction->date = new \KD2\DB\Date;
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