<?php

namespace Garradin\Entities\Accounting;

use KD2\DB\EntityManager;
use Garradin\Entity;
use Garradin\Accounting\Accounts;
use Garradin\ValidationException;
use Garradin\DB;
use Garradin\Config;

class Transaction extends Entity
{
	const TABLE = 'acc_transactions';

	protected $id;
	protected $label;
	protected $notes;
	protected $reference;

	protected $date;

	protected $validated = 0;

	protected $hash;
	protected $prev_hash;

	protected $id_year;

	protected $_types = [
		'id'        => 'int',
		'label'     => 'string',
		'notes'     => '?string',
		'reference' => '?string',
		'date'      => 'date',
		'validated' => 'bool',
		'hash'      => '?string',
		'prev_hash' => '?string',
		'id_year'   => 'int',
	];

	protected $_form_rules = [
		'label'     => 'required|string|max:200',
		'notes'     => 'string|max:20000',
		'reference' => 'string|max:200',
		'date'      => 'required|date_format:Y-m-d',
	];

	protected $_lines;

	public function getLinesWithAccounts()
	{
		$em = EntityManager::getInstance(Line::class);
		return $em->DB()->get('SELECT a.*, b.label AS account_name, b.code AS account_code FROM ' . Line::TABLE  .' a INNER JOIN ' . Account::TABLE . ' b ON b.id = a.id_account WHERE a.id_transaction = ? ORDER BY a.id;', $this->id);
	}

	public function getLines($with_accounts = false)
	{
		if (null === $this->_lines && $this->exists()) {
			$em = EntityManager::getInstance(Line::class);
			$this->_lines = $em->all('SELECT * FROM @TABLE WHERE id_transaction = ? ORDER BY id;', $this->id);
		}
		elseif (null === $this->_lines) {
			$this->_lines = [];
		}

		return $this->_lines;
	}

/*
	public function getHash()
	{
		if (!$this->id_year) {
			throw new \LogicException('Il n\'est pas possible de hasher un mouvement qui n\'est pas associé à un exercice');
		}

		static $keep_keys = [
			'label',
			'notes',
			'reference',
			'date',
			'validated',
			'prev_hash',
		];

		$hash = hash_init('sha256');
		$values = $this->asArray();
		$values = array_intersect_key($values, $keep_keys);

		hash_update($hash, implode(',', array_keys($values)));
		hash_update($hash, implode(',', $values));

		foreach ($this->getLines() as $line) {
			hash_update($hash, implode(',', [$line->compte, $line->debit, $line->credit]));
		}

		return hash_final($hash, false);
	}

	public function checkHash()
	{
		return hash_equals($this->getHash(), $this->hash);
	}
*/

	public function add(Line $line)
	{
		$this->_lines[] = $line;
	}

	public function save(): bool
	{
		if ($this->validated && !isset($this->_modified['validated'])) {
			throw new ValidationException('Il n\'est pas possible de modifier un mouvement qui a été validé');
		}

		if (!parent::save()) {
			return false;
		}

		foreach ($this->_lines as &$line)
		{
			$line->id_transaction = $this->id();
			$line->save();
		}

		return true;
	}

	public function delete(): bool
	{
		if ($this->validated) {
			throw new ValidationException('Il n\'est pas possible de supprimer un mouvement qui a été validé');
		}

		return parent::delete();
	}

	public function selfCheck(): void
	{
		parent::selfCheck();

		$db = DB::getInstance();
		$config = Config::getInstance();

		// ID d'exercice obligatoire
		if (null === $this->id_year) {
			throw new \LogicException('Aucun exercice spécifié.');
		}

		if (!$db->test(Year::TABLE, 'id = ? AND start_date <= ? AND end_date >= ?;', $this->id_year, $this->date, $this->date))
		{
			throw new ValidationException('La date ne correspond pas à l\'exercice sélectionné.');
		}

		$total = 0;

		$lines = $this->getLines();

		foreach ($lines as $line) {
			$total += $line->credit;
			$total -= $line->debit;
		}

		if (0 !== $total) {
			throw new ValidationException('Écriture non équilibrée : déséquilibre entre débits et crédits');
		}
	}

	public function importFromSimpleForm(int $chart_id, ?array $source = null): void
	{
		if (null === $source) {
			$source = $_POST;
		}

		if (empty($source['type'])) {
			throw new ValidationException('Type d\'écriture inconnu');
		}

		$type = $source['type'];

		$this->importForm();

		$accounts = new Accounts($chart_id);

		if ($type !== 'advanced') {
			$from = $accounts->getIdFromCode($source[$type . '_from']);
			$to = $accounts->getIdFromCode($source[$type . '_to']);
			$amount = $source['amount'];

			$line = new Line;
			$line->importForm([
				'reference'  => $source['payment_reference'],
				'credit' => '0',
				'debit'      => $amount,
				'id_account' => $from,
				'id_analytical' => $source['id_analytical'] ?? null,
			]);
			$this->add($line);

			$line = new Line;
			$line->importForm([
				'reference'  => $source['payment_reference'],
				'credit'     => $amount,
				'debit' => '0',
				'id_account' => $to,
				'id_analytical' => $source['id_analytical'] ?? null,
			]);
			$this->add($line);
		}
		else {
			foreach ($source['lines'] as $i => $line) {
				$line['id_account'] = $accounts->getIdFromCode($line['account']);

				if (!$line['id_account']) {
					throw new ValidationException('Numéro de compte invalide sur la ligne ' . ($i+1));
				}

				$line = (new Line)->import($line);
				$this->add($line);
			}
		}
	}

	public function year()
	{
		return EntityManager::findOneById(Year::class, $this->id_year);
	}

}