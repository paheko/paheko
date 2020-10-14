<?php

namespace Garradin\Entities\Accounting;

use DateTimeInterface;
use Garradin\Entity;
use Garradin\DB;
use Garradin\Utils;
use Garradin\UserException;
use Garradin\ValidationException;
use Garradin\Accounting\Charts;

class Account extends Entity
{
	const TABLE = 'acc_accounts';

	// Actif
	const ASSET = 1;

	// Passif
	const LIABILITY = 2;

	// Passif ou actif
	const ASSET_OR_LIABILITY = 3;

	// Produit
	const REVENUE = 4;

	// Charge
	const EXPENSE = 5;

	const POSITIONS_NAMES = [
		'',
		'Actif',
		'Passif',
		'Actif ou passif',
		'Produit',
		'Charge',
	];

	const TYPE_NONE = 0;
	const TYPE_REVENUE = 1;
	const TYPE_EXPENSE = 2;
	const TYPE_BANK = 3;
	const TYPE_CASH = 4;

	/**
	 * Outstanding transaction accounts (like cheque or card payments)
	 */
	const TYPE_OUTSTANDING = 5;

	const TYPE_ANALYTICAL = 6;
	const TYPE_VOLUNTEERING = 7;
	const TYPE_THIRD_PARTY = 8;

	const TYPE_OPENING = 9;
	const TYPE_CLOSING = 10;

	const TYPES_NAMES = [
		'',
		'Recettes',
		'Dépenses',
		'Banque',
		'Caisse',
		'Attente d\'encaissement',
		'Analytique',
		'Bénévolat',
		'Tiers',
		'Ouverture',
		'Clôture',
	];

	protected $id;
	protected $id_chart;
	protected $code;
	protected $label;
	protected $description;
	protected $position;
	protected $type;
	protected $user = 0;

	protected $_types = [
		'id'          => 'int',
		'id_chart'    => 'int',
		'code'        => 'string',
		'label'       => 'string',
		'description' => '?string',
		'position'    => 'int',
		'type'        => 'int',
		'user'        => 'int',
	];

	protected $_form_rules = [
		'code'        => 'required|string|alpha_num|max:10',
		'label'       => 'required|string|max:200',
		'description' => 'string|max:2000',
		'position'    => 'required|numeric|min:0',
		'type'        => 'required|numeric|min:0',
	];

	public function selfCheck(): void
	{
		$db = DB::getInstance();

		$this->assert(!empty($this->id_chart), 'Aucun plan comptable lié');

		$where = 'code = ? AND id_chart = ?';
		$where .= $this->exists() ? sprintf(' AND id != %d', $this->id()) : '';

		if ($db->test(self::TABLE, $where, $this->code, $this->id_chart)) {
			throw new ValidationException(sprintf('Le code "%s" est déjà utilisé par un autre compte.', $this->code));
		}

		$this->assert(array_key_exists($this->type, self::TYPES_NAMES), 'Type invalide');
		$this->assert(array_key_exists($this->position, self::POSITIONS_NAMES), 'Position invalide');
		$this->assert($this->user === 0 || $this->user === 1);

		parent::selfCheck();
	}

	public function getJournal(int $year_id)
	{
		$db = DB::getInstance();
		$sql = 'SELECT l.debit, l.credit, t.id, t.date, t.reference, l.reference AS line_reference, t.label, l.label AS line_label, l.reconciled
			FROM acc_transactions_lines l
			INNER JOIN acc_transactions t ON t.id = l.id_transaction
			WHERE l.id_account = ? AND t.id_year = ?
			ORDER BY t.date, t.id;';
		$rows = $db->get($sql, $this->id(), $year_id);

		$sum = 0;

		foreach ($rows as &$row) {
			$sum += ($row->credit - $row->debit);
			$row->running_sum = $sum;
			$row->date = \DateTime::createFromFormat('Y-m-d', $row->date);
		}

		return $rows;
	}

	public function getReconcileJournal(int $year_id, DateTimeInterface $start_date, DateTimeInterface $end_date, int &$start_sum, int &$end_sum)
	{
		if ($end_date < $start_date) {
			throw new ValidationException('La date de début ne peut être avant la date de fin.');
		}

		$db = DB::getInstance();
		$sql = 'SELECT l.debit, l.credit, t.id, t.date, t.reference, l.reference AS line_reference, t.label, l.label AS line_label, l.reconciled, l.id AS id_line
			FROM acc_transactions_lines l
			INNER JOIN acc_transactions t ON t.id = l.id_transaction
			WHERE l.id_account = ? AND t.id_year = ? AND t.date >= ? AND t.date <= ?
			ORDER BY t.date, t.id;';
		$rows = $db->iterate($sql, $this->id(), $year_id, $start_date->format('Y-m-d'), $end_date->format('Y-m-d'));

		$sum = $this->getSumAtDate($year_id, $start_date);

		$start_sum = false;

		foreach ($rows as $row) {
			if (!$start_sum) {
				yield ['sum' => $sum, 'date' => $start_date];
				$start_sum = true;
			}

			$row->date = \DateTime::createFromFormat('Y-m-d', $row->date);
			$sum += ($row->credit - $row->debit);
			$row->running_sum = $sum;

			yield $row;
		}

		yield ['sum' => $sum, 'date' => $end_date];
	}

	public function getSumAtDate(int $year_id, DateTimeInterface $date): int
	{
		return (int) DB::getInstance()->firstColumn('SELECT SUM(l.credit) - SUM(l.debit)
			FROM acc_transactions_lines l
			INNER JOIN acc_transactions t ON t.id = l.id_transaction
			wHERE l.id_account = ? AND t.id_year = ? AND t.date < ?
			ORDER BY t.date, t.id;', $this->id(), $year_id, $date->format('Y-m-d'));
	}

	public function importSimpleForm(array $translate_type_position, array $translate_type_codes, ?array $source = null)
	{
		if (null === $source) {
			$source = $_POST;
		}

		if (empty($source['type'])) {
			throw new UserException('Le type est obligatoire dans ce formulaire');
		}

		$type = (int) $source['type'];

		if (array_key_exists($type, $translate_type_position)) {
			$source['position'] = $translate_type_position[$type];
		}
		else {
			$source['position'] = self::ASSET_OR_LIABILITY;
		}

		if (array_key_exists($type, $translate_type_codes)) {
			$source['code'] = $translate_type_codes[$type];
		}

		$this->importForm($source);
	}

	public function importLimitedForm(?array $source = null)
	{
		if (null === $source) {
			$source = $_POST;
		}

		$data = array_intersect_key($source, array_flip(['type', 'description']));
		parent::import($data);
	}

	public function canDelete(): bool
	{
		return !DB::getInstance()->firstColumn(sprintf('SELECT 1 FROM %s WHERE id_account = ? LIMIT 1;', Line::TABLE), $this->id());
	}

	public function canEdit(): bool
	{
		return !DB::getInstance()->firstColumn(sprintf('SELECT 1 FROM %s l
			INNER JOIN %s t ON t.id = l.id_transaction
			INNER JOIN %s y ON y.id = t.id_year
			WHERE l.id_account = ? AND y.closed = 1
			LIMIT 1;', Line::TABLE, Transaction::TABLE, Year::TABLE), $this->id());
	}

	public function chart(): Chart
	{
		return Charts::get($this->id_chart);
	}
}