<?php

namespace Garradin\Accounting;

use Garradin\Entities\Accounting\Account;
use Garradin\CSV;
use Garradin\DB;
use Garradin\Utils;
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

	public function getIdFromCode(string $code): int
	{
		return $this->em->col('SELECT id FROM @TABLE WHERE code = ? AND id_chart = ?;', $code, $this->chart_id);
	}

	/**
	 * Return common accounting accounts from current chart
	 * (will not return analytical and volunteering accounts)
	 */
	public function listCommonTypes(): array
	{
		return $this->em->all('SELECT * FROM @TABLE WHERE id_chart = ? AND type != 0 AND type NOT IN (?, ?) ORDER BY code COLLATE NOCASE;',
			$this->chart_id, Account::TYPE_ANALYTICAL, Account::TYPE_VOLUNTEERING);
	}

	/**
	 * Return all accounts from current chart
	 */
	public function listAll(): array
	{
		return $this->em->all('SELECT * FROM @TABLE WHERE id_chart = ? ORDER BY code COLLATE NOCASE;',
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
		$res = $this->em->DB()->iterate($this->em->formatQuery('SELECT code, label, description, position, type FROM @TABLE WHERE id_chart = ? ORDER BY code COLLATE NOCASE;'),
			$this->chart_id);

		foreach ($res as $row) {
			$row->type = Account::TYPES_NAMES[$row->type];
			$row->position = Account::POSITIONS_NAMES[$row->position];
			yield $row;
		}
	}

	/**
	 * Return only analytical accounts
	 */
	public function listAnalytical(): array
	{
		return $this->em->DB()->getAssoc($this->em->formatQuery('SELECT id, label FROM @TABLE WHERE id_chart = ? AND type = ? ORDER BY code COLLATE NOCASE;'), $this->chart_id, Account::TYPE_ANALYTICAL);
	}

	/**
	 * Return only analytical accounts
	 */
	public function listVolunteering(): array
	{
		return $this->em->all('SELECT * FROM @TABLE WHERE id_chart = ? AND type = ? ORDER BY code COLLATE NOCASE;',
			$this->chart_id, Account::TYPE_VOLUNTEERING);
	}

	/**
	 * List common accounts, grouped by type
	 * @return array
	 */
	public function listCommonGrouped(array $types = null): array
	{
		if (null === $types) {
			$types = '';
		}
		else {
			$types = array_map('intval', $types);
			$types = ' AND ' . $this->em->DB()->where('type', $types);
		}

		$out = [];
		$query = $this->em->iterate('SELECT * FROM @TABLE WHERE id_chart = ? AND type != 0 ' . $types . ' ORDER BY type, code COLLATE NOCASE;',
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

	public function getNextCodesForTypes(): array
	{
		$db = DB::getInstance();
		$codes = $db->getAssoc(sprintf('SELECT type, MAX(code) FROM %s WHERE id_chart = ? AND type > 0 GROUP BY type;', Account::TABLE), $this->chart_id);

		foreach ($codes as &$code) {
			if (($letter = substr($code, -1)) && !is_numeric($letter)) {
				$code = substr($code, 0, -1);
				$letter = strtoupper($letter);
				$letter = ($letter == 'Z') ? 'AA' : chr(ord($letter)+1);
			}
			else {
				$letter = 'A';
			}

			$code = $code . $letter;
		}

		unset($code);
		return $codes;
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

	public function importCSV(string $file): void
	{
		$db = DB::getInstance();
		$positions = array_flip(Account::POSITIONS_NAMES);
		$types = array_flip(Account::TYPES_NAMES);

		$db->begin();

		try {
			foreach (CSV::import($file, self::EXPECTED_CSV_COLUMNS) as $line => $row) {
				$account = new Account;
				$account->id_chart = $this->chart_id;
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