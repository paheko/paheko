<?php

namespace Garradin\Entities\Accounting;

use Garradin\Entity;
use Garradin\validatedException;
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

	protected $validated;

	protected $hash;
	protected $prev_hash;

	protected $id_year;
	protected $id_analytical;

	protected $_types = [
		'label'             => 'string',
		'notes'             => '?string',
		'reference'         => '?string',
		'date'              => 'date',
		'validated'         => 'bool',
		'hash'              => '?string',
		'prev_hash'         => '?string',
		'id_year'           => 'int',
		'id_analytical'     => '?int',
	];

	protected $_validated_rules = [
		'label'             => 'required|string|max:200',
		'notes'             => 'string|max:20000',
		'reference'         => 'string|max:200',
		'date'              => 'required|date',
		'validated'         => 'bool',
		'id_year'           => 'integer|in_table:acc_years,id',
		'id_analytical'     => 'integer|in_table:acc_accounts,id'
	];

	protected $lines;

	public function getLines()
	{
		if (null === $this->lines && $this->exists()) {
			$db = DB::getInstance();
			$this->lines = $db->toObject($db->get('SELECT * FROM acc_transactions_lines WHERE id_transaction = ? ORDER BY id;', $this->id), Ligne::class);
		}
		else {
			$this->lines = [];
		}

		return $this->lines;
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

		foreach ($this->getLines() as $ligne) {
			hash_update($hash, implode(',', [$ligne->compte, $ligne->debit, $ligne->credit]));
		}

		return hash_final($hash, false);
	}

	public function checkHash()
	{
		return hash_equals($this->getHash(), $this->hash);
	}
*/

	public function add(Ligne $line)
	{
		$this->lines[] = $line;
	}

	public function transfer(int $amount, int $from, int $to)
	{
		$ligne1 = new Ligne;
		$ligne1->compte = $from;
		$ligne1->debit = $amount;
		$ligne1->credit = 0;

		$ligne2 = new Ligne;
		$ligne1->compte = $to;
		$ligne1->debit = 0;
		$ligne1->credit = $amount;

		return $this->add($ligne1) && $this->add($ligne2);
	}

	public function save()
	{
		if ($this->validated && !isset($this->_modified['validated'])) {
			throw new validatedException('Il n\'est pas possible de modifier un mouvement qui a été validé');
		}

		if (!parent::save()) {
			return false;
		}

		foreach ($this->lines as $ligne)
		{
			$ligne->id_transaction = $this->id;
			$ligne->save();
		}
	}

	public function delete()
	{
		if ($this->validated) {
			throw new validatedException('Il n\'est pas possible de supprimer un mouvement qui a été validé');
		}

		parent::delete();
	}

	public function selfCheck()
	{
		parent::selfCheck();

		$db = DB::getInstance();
		$config = Config::getInstance();

		// ID d'exercice obligatoire s'il existe déjà des exercices
		if (null === $this->id_year && $db->firstColumn('SELECT 1 FROM acc_years LIMIT 1;')) {
			throw new validatedException('Aucun exercice spécifié.');
		}

		if (null !== $this->id_year
			&& !$db->test('acc_years', 'id = ? AND start_date <= ? AND end_date >= ?;', $this->id_year, $this->date, $this->date))
		{
			throw new validatedException('La date ne correspond pas à l\'exercice sélectionné.');
		}

		$total = 0;

		$lines = $this->getLines();

		foreach ($lines as $line) {
			$total += $line->credit;
			$total -= $line->debit;
		}

		if (0 !== $total) {
			throw new validatedException('Mouvement non équilibré : déséquilibre entre débits et crédits');
		}
	}
}