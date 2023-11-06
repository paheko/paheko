<?php

namespace Paheko\Accounting;

use Paheko\Entities\Accounting\Account;
use Paheko\Entities\Accounting\Line;
use Paheko\Entities\Accounting\Project;
use Paheko\Entities\Accounting\Transaction;
use Paheko\Entities\Accounting\Year;
use KD2\DB\EntityManager;
use Paheko\DB;
use Paheko\DynamicList;
use Paheko\Utils;
use Paheko\UserException;

class Transactions
{
	static public function create(array $data): Transaction
	{
		$transaction = new Transaction;
		$transaction->importForm($data);
		return $transaction;
	}

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
					'label'      => $row->line_label ?? $row->label,
					'id_account' => $row->id_account,
					'id_project' => $row->id_project,
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
		return (int) DB::getInstance()->firstColumn('SELECT COUNT(*) FROM acc_transactions_users WHERE id_user = ? GROUP BY id_transaction;', $user_id);
	}

	static public function countForCreator(int $user_id): int
	{
		return DB::getInstance()->count('acc_transactions', 'id_creator = ?', $user_id);
	}

	/**
	 * Returns a dynamic list of all waiting credit and debt transactions for closed years
	 */
	static public function listPendingCreditAndDebtForOtherYears(int $current_year_id): DynamicList
	{
		$columns = Account::LIST_COLUMNS;

		unset(
			$columns['line_label'],
			$columns['sum'],
			$columns['debit'],
			$columns['credit'],
			$columns['project_code'],
			$columns['id_project'],
			$columns['line_reference'],
			$columns['locked'],
			$columns['files']
		);

		$columns['change']['select'] = 'SUM(l.credit)';
		$columns['change']['label'] = 'Montant';

		$columns = [
			'year_label' => [
				'select' => 'y.label',
				'label' => 'Exercice',
			],
			'type_label' => [
				'select' => 't.type',
				'label' => 'Type',
			]]
			+ $columns;

		$conditions = sprintf('y.id != %d AND t.status & %d AND t.type IN (%d, %d)',
			$current_year_id,
			Transaction::STATUS_WAITING,
			Transaction::TYPE_CREDIT,
			Transaction::TYPE_DEBT
		);

		$tables = 'acc_transactions_lines l
			INNER JOIN acc_transactions t ON t.id = l.id_transaction
			INNER JOIN acc_years y ON y.id = t.id_year';

		$list = new DynamicList($columns, $tables, $conditions);
		$list->orderBy('date', true);
		$list->setCount('COUNT(DISTINCT t.id)');
		$list->groupBy('t.id');
		$list->setModifier(function (&$row) {
			$row->date = \DateTime::createFromFormat('!Y-m-d', $row->date);

			if (isset($row->type_label)) {
				$row->type_label = Transaction::TYPES_NAMES[(int)$row->type_label];
			}
		});

		$list->setExportCallback(function (&$row) {
			$row->change = Utils::money_format($row->change, '.', '', false);
		});

		$list->setTitle('Dettes et créances en attente');

		return $list;
	}

	static public function setProject(?int $id_project, ?array $transactions = null, ?array $lines = null)
	{
		$db = DB::getInstance();

		if (null !== $id_project && !$db->test(Project::TABLE, 'id = ?', $id_project)) {
			throw new \InvalidArgumentException('Invalid project ID');
		}

		if (isset($transactions, $lines) || ($transactions === null && $lines === null)) {
			throw new \BadMethodCallException('Only one of transactions or lines should be set');
		}

		$selection = array_map('intval', $transactions ?? $lines);
		$where = sprintf($transactions ? 'id_transaction IN (%s)' : 'id IN (%s)', implode(', ', $selection));

		return $db->exec(sprintf('UPDATE acc_transactions_lines SET id_project = %s WHERE %s;',
			(int)$id_project ?: 'NULL', $where));
	}

	static public function listByType(int $year_id, ?int $type): DynamicList
	{
		$reverse = 1;

		$columns = Account::LIST_COLUMNS;

		unset(
			$columns['line_label'],
			$columns['sum'],
			$columns['debit'],
			$columns['credit']
		);

		$db = DB::getInstance();

		// Don't show locked column if no transactions are locked
		if (!$db->test('acc_transactions', 'hash IS NOT NULL')) {
			unset($columns['locked']);
		}

		$columns['line_reference']['label'] = 'Réf. paiement';
		$columns['change']['select'] = sprintf('SUM(l.credit) * %d', $reverse);
		$columns['change']['label'] = 'Montant';
		$columns['project_code']['select'] = 'GROUP_CONCAT(IFNULL(b.code, SUBSTR(b.label, 1, 10) || \'…\'), \',\')';
		$columns['id_project']['select'] = 'GROUP_CONCAT(l.id_project, \',\')';

		if ($type == Transaction::TYPE_CREDIT || $type == Transaction::TYPE_DEBT) {

			$columns['status_label'] = [
				'label' => 'Statut',
				'select' => sprintf('CASE WHEN t.status & %d THEN %s WHEN t.status & %d THEN %s ELSE NULL END',
					Transaction::STATUS_WAITING, $db->quote('En attente'),
					Transaction::STATUS_PAID, $db->quote('Réglée')
				),
			];
		}

		if (!$type) {
			$columns = ['type_label' => [
					'select' => 't.type',
					'label' => 'Type d\'écriture',
				]]
				+ $columns;
		}

		$tables = 'acc_transactions_lines l
			INNER JOIN acc_transactions t ON t.id = l.id_transaction
			INNER JOIN acc_accounts a ON a.id = l.id_account
			LEFT JOIN acc_projects b ON b.id = l.id_project';
		$conditions = sprintf('t.id_year = %d', $year_id);

		if (null !== $type) {
			$conditions .= sprintf(' AND t.type = %s', $type);
		}

		$sum = 0;

		$list = new DynamicList($columns, $tables, $conditions);
		$list->orderBy('date', true);
		$list->setCount('COUNT(t.id)');
		$list->setCountTables('acc_transactions t');
		$list->groupBy('t.id');
		$list->setModifier(function (&$row) {
			$row->date = \DateTime::createFromFormat('!Y-m-d', $row->date);

			if (isset($row->id_project, $row->project_code)) {
				$row->project_code = array_combine(explode(',', $row->id_project), explode(',', $row->project_code));
			}
			else {
				$row->project_code = [];
			}

			if (isset($row->type_label)) {
				$row->type_label = Transaction::TYPES_NAMES[(int)$row->type_label];
			}
		});
		$list->setExportCallback(function (&$row) {
			$row->change = Utils::money_format($row->change, '.', '', false);
			$row->project_code = implode(', ', $row->project_code);
			unset($row->id_project);
		});

		return $list;
	}
}
