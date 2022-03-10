<?php

namespace Garradin\Accounting;

use Garradin\Entities\Accounting\Account;
use Garradin\Entities\Accounting\Line;
use Garradin\Entities\Accounting\Transaction;
use Garradin\Entities\Accounting\Year;
use KD2\DB\EntityManager;
use Garradin\Config;
use Garradin\CSV;
use Garradin\CSV_Custom;
use Garradin\DB;
use Garradin\DynamicList;
use Garradin\Utils;
use Garradin\UserException;

class Transactions
{
	const EXPORT_FULL = 'full';
	const EXPORT_GROUPED = 'grouped';
	const EXPORT_SIMPLE = 'simple';

	const EXPORT_NAMES = [
		self::EXPORT_FULL => 'Complet',
		self::EXPORT_GROUPED => 'Groupé',
		self::EXPORT_SIMPLE => 'Simplifié',
	];

	const EXPORT_COLUMNS_FULL = [
		'id'        => 'Numéro d\'écriture',
		'type'      => 'Type',
		'status'    => 'Statut',
		'label'     => 'Libellé',
		'date'      => 'Date',
		'notes'     => 'Remarques',
		'reference' => 'Numéro pièce comptable',

		// Lines
		'line_id'        => 'Numéro ligne',
		'account'        => 'Compte',
		'credit'         => 'Crédit',
		'debit'          => 'Débit',
		'line_reference' => 'Référence ligne',
		'line_label'     => 'Libellé ligne',
		'reconciled'     => 'Rapprochement',
		'analytical'     => 'Compte analytique',
		'linked_users'   => 'Membres associés',
	];

	const EXPORT_COLUMNS = [
		self::EXPORT_GROUPED => self::EXPORT_COLUMNS_FULL,
		self::EXPORT_FULL => self::EXPORT_COLUMNS_FULL,
		self::EXPORT_SIMPLE => [
			'id'             => 'Numéro d\'écriture',
			'type'           => 'Type',
			'status'         => 'Statut',
			'label'          => 'Libellé',
			'date'           => 'Date',
			'notes'          => 'Remarques',
			'reference'      => 'Numéro pièce comptable',
			'p_reference'    => 'Référence paiement',
			'debit_account'  => 'Compte de débit',
			'credit_account' => 'Compte de crédit',
			'amount'         => 'Montant',
			'analytical'     => 'Compte analytique',
			'linked_users'   => 'Membres associés',
		],
	];

	const MANDATORY_COLUMNS = [
		self::EXPORT_GROUPED => [
			'type',
			'label',
			'date',
			'account',
			'credit',
			'debit',
		],
		self::EXPORT_SIMPLE => [
			'label',
			'date',
			'credit_account',
			'debit_account',
			'amount'
		],
	];

	static public function get(int $id)
	{
		return EntityManager::findOneById(Transaction::class, $id);
	}

	static public function saveReconciled(\Generator $journal, ?array $checked)
	{
		if (null === $checked) {
			$checked = [];
		}

		$db = DB::getInstance();
		$db->begin();

		// Synchro des trucs cochés
		$st = $db->prepare('UPDATE acc_transactions_lines SET reconciled = :r WHERE id = :id;');

		foreach ($journal as $row)
		{
			if (!isset($row->id_line)) {
				continue;
			}

			$st->bindValue(':id', (int)$row->id_line, \SQLITE3_INTEGER);
			$st->bindValue(':r', !empty($checked[$row->id_line]) ? 1 : 0, \SQLITE3_INTEGER);
			$st->execute();
		}

		$db->commit();
	}

	static public function saveDeposit(Transaction $transaction, \Generator $journal, array $checked)
	{
		$db = DB::getInstance();
		$db->begin();

		try {
			$ids = [];
			foreach ($journal as $row) {
				if (!array_key_exists($row->id_line, $checked)) {
					continue;
				}

				$ids[] = (int)$row->id;

				$line = new Line;
				$line->importForm([
					'reference'  => $row->line_reference,
					'id_account' => $row->id_account,
				]);

				$line->credit = $row->debit;

				$transaction->addLine($line);
			}

			$transaction->save();
			$ids = implode(',', $ids);
			$db->exec(sprintf('UPDATE acc_transactions SET status = (status | %d) WHERE id IN (%s);', Transaction::STATUS_DEPOSIT, $ids));
			$db->commit();
		}
		catch (\Exception $e) {
			$db->rollback();
			throw $e;
		}
	}

	static public function countForUser(int $user_id): int
	{
		return DB::getInstance()->count('acc_transactions_users', 'id_user = ?', $user_id);
	}

	static public function countForCreator(int $user_id): int
	{
		return DB::getInstance()->count('acc_transactions', 'id_creator = ?', $user_id);
	}

	/**
	 * Return all transactions from year
	 */
	static public function export(Year $year, string $format, string $type): void
	{
		$header = null;

		if (!array_key_exists($type, self::EXPORT_COLUMNS)) {
			throw new \InvalidArgumentException('Unknown type: ' . $type);
		}

		CSV::export(
			$format,
			sprintf('Export comptable %s - %s - %s', strtolower(self::EXPORT_NAMES[$type]), Config::getInstance()->get('nom_asso'), $year->label),
			self::iterateExport($year->id(), $type),
			array_values(self::EXPORT_COLUMNS[$type])
		);
	}

	static public function getExportExamples(Year $year)
	{
		$out = [];

		foreach (self::EXPORT_NAMES as $type => $label) {
			$i = 0;
			$out[$type] = [self::EXPORT_COLUMNS[$type]];

			foreach (self::iterateExport($year->id(), $type) as $row) {
				$out[$type][] = $row;

				if (++$i > 1) {
					break;
				}
			}
		}

		return $out;
	}

	static protected function iterateExport(int $year_id, string $type): \Generator
	{
		$id_field = Config::getInstance()->get('champ_identite');

		if (self::EXPORT_SIMPLE == $type) {
			$sql =  'SELECT t.id, t.type, t.status, t.label, t.date, t.notes, t.reference,
				l1.reference AS p_reference,
				a1.code AS debit_account,
				a2.code AS credit_account,
				l1.debit AS amount,
				a3.code AS analytical,
				GROUP_CONCAT(u.%s) AS linked_users
				FROM acc_transactions t
				INNER JOIN acc_transactions_lines l1 ON l1.id_transaction = t.id AND l1.debit != 0
				INNER JOIN acc_transactions_lines l2 ON l2.id_transaction = t.id AND l2.credit != 0
				INNER JOIN acc_accounts a1 ON a1.id = l1.id_account
				INNER JOIN acc_accounts a2 ON a2.id = l2.id_account
				LEFT JOIN acc_accounts a3 ON a3.id = l1.id_analytical
				LEFT JOIN acc_transactions_users tu ON tu.id_transaction = t.id
				LEFT JOIN membres u ON u.id = tu.id_user
				WHERE t.id_year = ?
					AND t.type != %d
				GROUP BY t.id
				ORDER BY t.date, t.id;';

			$sql = sprintf($sql, $id_field, Transaction::TYPE_ADVANCED);
		}
		else {
			$sql = 'SELECT t.id, t.type, t.status, t.label, t.date, t.notes, t.reference,
				l.id AS line_id, a.code AS account, l.debit AS debit, l.credit AS credit,
				l.reference AS line_reference, l.label AS line_label, l.reconciled,
				a2.code AS analytical,
				GROUP_CONCAT(u.%s) AS linked_users
				FROM acc_transactions t
				INNER JOIN acc_transactions_lines l ON l.id_transaction = t.id
				INNER JOIN acc_accounts a ON a.id = l.id_account
				LEFT JOIN acc_accounts a2 ON a2.id = l.id_analytical
				LEFT JOIN acc_transactions_users tu ON tu.id_transaction = t.id
				LEFT JOIN membres u ON u.id = tu.id_user
				WHERE t.id_year = ?
				GROUP BY t.id, l.id
				ORDER BY t.date, t.id, l.id;';

			$sql = sprintf($sql, $id_field);
		}

		$res = DB::getInstance()->iterate($sql, $year_id);

		$previous_id = null;

		foreach ($res as $row) {
			if ($previous_id === $row->id && $type == self::EXPORT_GROUPED) {
				// Remove transaction data to differentiate lines and transactions
				$row->id = $row->type = $row->status = $row->label = $row->date = $row->notes = $row->reference = null;
			}
			else {
				$row->type = Transaction::TYPES_NAMES[$row->type];

				$status = [];

				foreach (Transaction::STATUS_NAMES as $k => $v) {
					if ($row->status & $k) {
						$status[] = $v;
					}
				}

				$row->status = implode(', ', $status);
				$row->date = \DateTime::createFromFormat('Y-m-d', $row->date);
				$row->date = $row->date->format('d/m/Y');
				$previous_id = $row->id;
			}

			if ($type == self::EXPORT_SIMPLE) {
				$row->amount = Utils::money_format($row->amount, ',', '');
			}
			else {
				$row->credit = Utils::money_format($row->credit, ',', '');
				$row->debit = Utils::money_format($row->debit, ',', '');
			}

			yield $row;
		}
	}

	static public function import(string $type, Year $year, CSV_Custom $csv, int $user_id, bool $ignore_ids = false)
	{
		if ($type != self::EXPORT_GROUPED && $type != self::EXPORT_SIMPLE) {
			throw new \InvalidArgumentException('Invalid type value');
		}

		if ($year->closed) {
			throw new \InvalidArgumentException('Closed year');
		}

		$db = DB::getInstance();
		$db->begin();

		$accounts = $year->accounts();
		$transaction = null;
		$types = array_flip(Transaction::TYPES_NAMES);

		$l = 1;

		try {
			foreach ($csv->iterate() as $l => $row) {
				$row = (object) $row;

				// Import grouped transactions
				if ($type == self::EXPORT_GROUPED) {
					// If a line doesn't have any transaction info: this is a line following the previous transaction
					$has_transaction = !(empty($row->id)
						&& empty($row->type)
						&& empty($row->status)
						&& empty($row->label)
						&& empty($row->date)
						&& empty($row->notes)
						&& empty($row->reference)
					);

					// New transaction, save previous one
					if (null !== $transaction && $has_transaction) {
						$transaction->save();
						$transaction = null;
					}

					if (!$has_transaction && null === $transaction) {
						throw new UserException('cette ligne n\'est reliée à aucune écriture');
					}
				}
				else {
					if (empty($row->type)) {
						$row->type = Transaction::TYPES_NAMES[Transaction::TYPE_ADVANCED];
					}

					$transaction = null;
				}

				// Find or create transaction
				if (null === $transaction) {
					if (!empty($row->id) && !$ignore_ids) {
						$transaction = self::get((int)$row->id);

						if (!$transaction) {
							throw new UserException(sprintf('l\'écriture #%d est introuvable', $row->id));
						}

						if ($transaction->id_year != $year->id()) {
							throw new UserException(sprintf('l\'écriture #%d appartient à un autre exercice', $row->id));
						}

						if ($transaction->validated) {
							throw new UserException(sprintf('l\'écriture #%d est validée et ne peut être modifiée', $row->id));
						}
					}
					else {
						$transaction = new Transaction;
						$transaction->id_creator = $user_id;
						$transaction->id_year = $year->id();
					}

					if (!isset($types[$row->type])) {
						throw new UserException(sprintf('le type "%s" est inconnu', $row->type));
					}

					$transaction->type = $types[$row->type];
					$fields = array_intersect_key((array)$row, array_flip(['label', 'date', 'notes', 'reference']));

					$transaction->importForm($fields);
				}

				$data = [];

				if (!empty($row->analytical)) {
					$id_analytical = $accounts->getIdFromCode($row->analytical);

					if (!$id_analytical) {
						throw new UserException(sprintf('le compte analytique "%s" n\'existe pas dans le plan comptable', $row->analytical));
					}

					$data['id_analytical'] = $id_analytical;
				}
				elseif (property_exists($row, 'analytical')) {
					$data['id_analytical'] = null;
				}

				// Add two transaction lines for each CSV line
				if ($type == self::EXPORT_SIMPLE) {
					$credit_account = $accounts->getIdFromCode($row->credit_account);
					$debit_account = $accounts->getIdFromCode($row->debit_account);

					if (!$credit_account) {
						throw new UserException(sprintf('Compte de crédit "%s" inconnu dans le plan comptable', $row->credit_account));
					}

					if (!$debit_account) {
						throw new UserException(sprintf('Compte de débit "%s" inconnu dans le plan comptable', $row->debit_account));
					}

					$data['reference'] = isset($row->p_reference) ? $row->p_reference : null;

					if (!$transaction->exists()) {
						$l1 = new Line;
						$l2 = new Line;
						$transaction->addLine($l1);
						$transaction->addLine($l2);
					}
					else {
						$lines = $transaction->getLines();

						if (count($lines) != 2) {
							throw new UserException('cette écriture comporte plus de deux lignes et ne peut donc être modifiée par un import simplifié');
						}

						// Find correct debit/credit lines
						if ($lines[0]->credit != 0) {
							$l1 = $lines[0];
							$l2 = $lines[1];
						}
						else {
							$l1 = $lines[1];
							$l2 = $lines[0];
						}
					}

					$l1->importForm($data + [
						'credit'     => $row->amount,
						'debit'      => 0,
						'id_account' => $credit_account,
					]);

					$l2->importForm($data + [
						'credit'     => 0,
						'debit'      => $row->amount,
						'id_account' => $debit_account,
					]);

					$transaction->save();
					$transaction = null;
				}
				else {
					$id_account = $accounts->getIdFromCode($row->account);

					if (!$id_account) {
						throw new UserException(sprintf('le compte "%s" n\'existe pas dans le plan comptable', $row->account));
					}

					$data = $data + [
						'credit'     => $row->credit ?: 0,
						'debit'      => $row->debit ?: 0,
						'id_account' => $id_account,
						'reference'  => $row->line_reference ?? null,
						'label'      => $row->line_label ?? null,
						'reconciled' => $row->reconciled ?? false,
					];

					if (!empty($row->line_id) && !$ignore_ids) {
						$line = $transaction->getLine((int)$row->line_id);

						if (!$line) {
							throw new UserException(sprintf('le numéro de ligne "%s" n\'existe pas dans l\'écriture "%s"', $row->line_id, $transaction->id ?: 'à créer'));
						}
					}
					else {
						$line = new Line;
					}

					$line->importForm($data);

					// If a line_id was supplied, just changing the object is enough, no need to add it to the transaction
					if (empty($row->line_id)) {
						$transaction->addLine($line);
					}
				}
			}

			if (null !== $transaction) {
				$transaction->save();
			}
		}
		catch (UserException $e) {
			$db->rollback();
			$e->setMessage(sprintf('Erreur sur la ligne %d : %s', $l, $e->getMessage()));

			if (null !== $transaction) {
				$e->setDetails($transaction->asDetailsArray());
			}

			throw $e;
		}
		$db->commit();
	}

	static public function setAnalytical(?int $id_analytical, ?array $transactions = null, ?array $lines = null)
	{
		$db = DB::getInstance();

		if (null !== $id_analytical && !$db->test(Account::TABLE, 'type = ? AND id = ?', Account::TYPE_ANALYTICAL, $id_analytical)) {
			throw new \InvalidArgumentException('Chosen account ID is not analytical');
		}

		if (isset($transactions, $lines) || ($transactions === null && $lines === null)) {
			throw new \BadMethodCallException('Only one of transactions or lines should be set');
		}

		$selection = array_map('intval', $transactions ?? $lines);
		$where = sprintf($transactions ? 'id_transaction IN (%s)' : 'id IN (%s)', implode(', ', $selection));

		return $db->exec(sprintf('UPDATE acc_transactions_lines SET id_analytical = %s WHERE %s;',
			(int)$id_analytical ?: 'NULL', $where));
	}

	static public function listByType(int $year_id, ?int $type)
	{
		$reverse = 1;

		$columns = Account::LIST_COLUMNS;
		unset($columns['line_label'], $columns['sum'], $columns['debit'], $columns['credit']);
		$columns['line_reference']['label'] = 'Réf. paiement';
		$columns['change']['select'] = sprintf('SUM(l.credit) * %d', $reverse);
		$columns['change']['label'] = 'Montant';
		$columns['code_analytical']['select'] = 'GROUP_CONCAT(b.code, \',\')';
		$columns['id_analytical']['select'] = 'GROUP_CONCAT(l.id_analytical, \',\')';

		$tables = 'acc_transactions_lines l
			INNER JOIN acc_transactions t ON t.id = l.id_transaction
			INNER JOIN acc_accounts a ON a.id = l.id_account
			LEFT JOIN acc_accounts b ON b.id = l.id_analytical';
		$conditions = sprintf('t.id_year = %d', $year_id);

		if (null !== $type) {
			$conditions .= sprintf(' AND t.type = %s', $type);
		}

		$sum = 0;

		$list = new DynamicList($columns, $tables, $conditions);
		$list->orderBy('date', true);
		$list->setCount('COUNT(DISTINCT t.id)');
		$list->groupBy('t.id');
		$list->setModifier(function (&$row) {
			$row->date = \DateTime::createFromFormat('!Y-m-d', $row->date);

			if (isset($row->id_analytical, $row->code_analytical)) {
				$row->code_analytical = array_combine(explode(',', $row->id_analytical), explode(',', $row->code_analytical));
			}
			else {
				$row->code_analytical = [];
			}
		});
		$list->setExportCallback(function (&$row) {
			$row->change = Utils::money_format($row->change, '.', '', false);
			$row->code_analytical = implode(', ', $row->code_analytical);
		});

		return $list;
	}
}
