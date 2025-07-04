<?php

namespace Paheko\Entities\Accounting;

use KD2\DB\EntityManager;
use KD2\DB\Date;
use Paheko\DB;
use Paheko\Entity;
use Paheko\Log;
use Paheko\UserException;
use Paheko\Utils;
use Paheko\ValidationException;
use Paheko\Accounting\Accounts;
use Paheko\Files\Files;
use Paheko\Entities\Files\File;
use Paheko\Users\Session;

class Year extends Entity
{
	const NAME = 'Exercice';
	const PRIVATE_URL = '!acc/reports/graphs.php?year=%d';

	const TABLE = 'acc_years';

	protected int $id;
	protected string $label;
	protected Date $start_date;
	protected Date $end_date;
	protected int $status = self::OPEN;
	protected int $id_chart;

	const OPEN = 0;
	const CLOSED = 1;
	const LOCKED = 2;

	const STATUS_TAG_PRESETS = [
		self::OPEN   => 'open',
		self::CLOSED => 'closed',
		self::LOCKED => 'locked',
	];

	const STATUS_LABELS = [
		self::OPEN   => 'en cours',
		self::CLOSED => 'clôturé',
		self::LOCKED => 'verrouillé',
	];

	public function selfCheck(): void
	{
		$this->assert(trim($this->label) !== '', 'Le libellé ne peut rester vide.');
		$this->assert(strlen($this->label) <= 200, 'Le libellé ne peut faire plus de 200 caractères.');
		$this->assert($this->start_date instanceof \DateTime, 'La date de début de l\'exercice n\'est pas définie.');
		$this->assert($this->end_date instanceof \DateTime, 'La date de début de l\'exercice n\'est pas définie.');

		$this->assert($this->start_date < $this->end_date, 'La date de fin doit être postérieure à la date de début');

		$db = DB::getInstance();

		$this->assert(isset($this->id_chart));
		parent::selfCheck();

		if ($this->exists()) {
			$this->assert(
				!$db->test(Transaction::TABLE, 'id_year = ? AND date < ?', $this->id(), $this->start_date->format('Y-m-d')),
				'Des écritures de cet exercice ont une date antérieure à la date de début de l\'exercice.'
			);

			$this->assert(
				!$db->test(Transaction::TABLE, 'id_year = ? AND date > ?', $this->id(), $this->end_date->format('Y-m-d')),
				'Des écritures de cet exercice ont une date postérieure à la date de fin de l\'exercice.'
			);
		}
	}

	public function isOpen(): bool
	{
		return $this->status === self::OPEN;
	}

	public function isClosed(): bool
	{
		return $this->status === self::CLOSED;
	}

	public function isLocked(): bool
	{
		return $this->status === self::LOCKED;
	}

	public function getStatusTagPreset(): string
	{
		return self::STATUS_TAG_PRESETS[$this->status];
	}

	public function getStatusLabel(): string
	{
		return self::STATUS_LABELS[$this->status];
	}

	public function close(): void
	{
		$this->assertCanBeModified();

		$this->set('status', self::CLOSED);
		$this->save();
	}

	public function reopen(?int $user_id): void
	{
		if ($this->isOpen()) {
			throw new \LogicException('This year is already open');
		}

		$closing_id = $this->accounts()->getClosingAccountId();

		if (!$closing_id) {
			throw new UserException('Aucun compte n\'est indiqué comme compte de clôture dans le plan comptable');
		}

		$this->set('status', self::OPEN);
		$this->save();

		Log::add(Log::MESSAGE, [
			'message' => 'Réouverture de l\'exercice',
			'entity'  => self::class,
			'id'      => $this->id(),
		]);

		// Create validated transaction to show that someone has reopened the year
		$t = new Transaction;
		$t->import([
			'id_year'    => $this->id(),
			'label'      => sprintf('Exercice réouvert le %s', date('d/m/Y à H:i:s')),
			'type'       => Transaction::TYPE_ADVANCED,
			'date'       => $this->end_date->format('d/m/Y'),
			'id_creator' => $user_id,
			'notes'      => 'Écriture automatique créée lors de la réouverture, à des fins de transparence. Cette écriture ne peut pas être supprimée ni modifiée.',
		]);

		$line = new Line;
		$line->import([
			'debit' => 0,
			'credit' => 1,
			'id_account' => $closing_id,
		]);
		$t->addLine($line);

		$line = new Line;
		$line->import([
			'debit'      => 1,
			'credit'     => 0,
			'id_account' => $closing_id,
		]);
		$t->addLine($line);

		// Lock transaction
		$t->lock();

		$t->save();
	}

	/**
	 * Splits an accounting year between the current year and another one, at a given date
	 * Any transaction between the given dates will be moved to the target year.
	 */
	public function split(\DateTime $start, \DateTime $end, Year $target): void
	{
		$this->assertCanBeModified();
		$target->assertCanBeModified();

		if ($start < $target->start_date || $start > $target->end_date) {
			throw new ValidationException('La date de début ne correspond pas à l\'exercice cible choisi.');
		}
		elseif ($end < $target->start_date || $end > $target->end_date) {
			throw new ValidationException('La date de fin ne correspond pas à l\'exercice cible choisi.');
		}

		DB::getInstance()->preparedQuery('UPDATE acc_transactions
			SET id_year = ?
			WHERE id_year = ? AND date >= ? AND date <= ?;',
			$target->id(),
			$this->id(),
			$start->format('Y-m-d'),
			$end->format('Y-m-d')
		);
	}

	public function delete(): bool
	{
		$db = DB::getInstance();
		$ids = $db->getAssoc('SELECT id, id FROM acc_transactions WHERE id_year = ?;', $this->id());


		// Delete all files
		foreach ($ids as $id) {
			Files::delete(File::CONTEXT_TRANSACTION . '/' . $id);
		}

		// Manual delete of transactions, as there is a voluntary safeguard in SQL: no cascade
		$db->preparedQuery('DELETE FROM acc_transactions WHERE id_year = ?;', $this->id());

		return parent::delete();
	}

	public function zipAllAttachments(?string $target, ?Session $session): void
	{
		$db = DB::getInstance();
		$dirs = $db->getAssoc('SELECT t.id, ? || \'/\' || t.id
			FROM acc_transactions t
			INNER JOIN acc_transactions_files f ON f.id_transaction = t.id
			WHERE t.id_year = ?
			GROUP BY t.id;',
			File::CONTEXT_TRANSACTION,
			$this->id()
		);

		Files::zip($dirs, $target, $session, $this->label .' - Fichiers joints');
	}

	public function countTransactions(): int
	{
		$db = DB::getInstance();
		return $db->count(Transaction::TABLE, $db->where('id_year', $this->id()));
	}

	public function chart()
	{
		return EntityManager::findOneById(Chart::class, $this->id_chart);
	}

	public function accounts()
	{
		return new Accounts($this->id_chart);
	}

	public function label_years()
	{
		$start = Utils::date_fr($this->start_date, 'Y');
		$end = Utils::date_fr($this->end_date, 'Y');
		return $start == $end ? $start : sprintf('%s-%s', $start, substr($end, -2));
	}

	public function getLabelWithYearsAndStatus()
	{
		return sprintf('%s — %s au %s (%s)',
			$this->label,
			Utils::shortDate($this->start_date),
			Utils::shortDate($this->end_date),
			$this->getStatusLabel()
		);
	}

	public function hasOpeningBalance(): bool
	{
		return DB::getInstance()->test(Transaction::TABLE, 'id_year = ? AND status & ?', $this->id(), Transaction::STATUS_OPENING_BALANCE);
	}

	public function deleteOpeningBalance(): void
	{
		$em = EntityManager::getInstance(Transaction::class);
		$list = $em->iterate('SELECT * FROM @TABLE WHERE id_year = ? AND status & ?', $this->id(), Transaction::STATUS_OPENING_BALANCE);

		foreach ($list as $t) {
			$t->delete();
		}
	}

	public function assertCanBeModified(bool $locked_check = true): void
	{
		if ($locked_check && $this->isLocked()) {
			throw new UserException('Impossible de modifier un exercice verrouillé.');
		}

		if ($this->isClosed()) {
			throw new UserException('Impossible de modifier un exercice clôturé.');
		}
	}

	public function importForm(?array $source = null)
	{
		$this->assertCanBeModified(false);

		$source ??= $_POST;

		if (!empty($source['locked_present'])) {
			$source['status'] = !empty($source['locked']) ? self::LOCKED : self::OPEN;
		}

		return parent::importForm($source);
	}

	public function saveProvisional(array $lines)
	{
		$db = DB::getInstance();
		$db->begin();

		$db->preparedQuery('DELETE FROM acc_years_provisional WHERE id_year = ?;', $this->id());

		foreach ($lines as $row) {
			if (empty($row['id_account'])) {
				continue;
			}

			$db->insert('acc_years_provisional', [
				'id_year'    => $this->id(),
				'id_account' => $row['id_account'],
				'amount'     => $row['amount'],
			], 'OR IGNORE');
		}

		$db->commit();
	}

	public function getProvisional(): array
	{
		$db = DB::getInstance();
		$sql = 'SELECT p.amount, a.code, a.label, a.code || \' — \' || a.label AS selector_label, a.id AS id_account
			FROM acc_years_provisional p
			INNER JOIN acc_accounts a ON a.id = p.id_account
			WHERE p.id_year = ? AND a.position = ?
			ORDER BY a.code COLLATE NOCASE;';

		$out = [
			'expense'       => $db->get($sql, $this->id(), Account::EXPENSE),
			'revenue'       => $db->get($sql, $this->id(), Account::REVENUE),
			'expense_total' => 0,
			'revenue_total' => 0,
			'result'        => 0,
		];

		foreach ($out as $key => $list) {
			if (!is_array($list)) {
				continue;
			}

			foreach ($list as $i => $item) {
				$out[$key][$i]->account_selector = [$item->id_account => $item->selector_label];
				$out[$key . '_total'] += $item->amount;
			}
		}

		$out['result'] = $out['revenue_total'] - $out['expense_total'];

		return $out;
	}

	public function listAccountsWithMissingDepositsFromOtherYears(): array
	{
		$sql = 'SELECT a.label, a.code, a.id
			FROM acc_transactions t
			INNER JOIN acc_transactions_lines l ON l.id_transaction = t.id
			INNER JOIN acc_accounts a ON a.id = l.id_account
			WHERE t.id_year != ?
				AND a.type = ?
				AND l.credit = 0
				AND NOT (t.status & ?)
				AND NOT (t.status & ?)
			GROUP BY a.code
			ORDER BY a.label COLLATE U_NOCASE;';

		return DB::getInstance()->get($sql,
			$this->id(),
			Account::TYPE_OUTSTANDING,
			Transaction::STATUS_DEPOSITED,
			Transaction::STATUS_OPENING_BALANCE
		);
	}
}
