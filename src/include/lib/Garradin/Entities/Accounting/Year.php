<?php

namespace Garradin\Entities\Accounting;

use KD2\DB\EntityManager;
use Garradin\Entity;
use Garradin\DB;
use Garradin\UserException;
use Garradin\Accounting\Accounts;

class Year extends Entity
{
	const TABLE = 'acc_years';

	protected $id;
	protected $label;
	protected $start_date;
	protected $end_date;
	protected $closed = 0;
	protected $id_chart;

	protected $_types = [
		'id'         => 'int',
		'label'      => 'string',
		'start_date' => 'date',
		'end_date'   => 'date',
		'closed'     => 'int',
		'id_chart'   => 'int',
	];

	protected $_form_rules = [
		'label'      => 'required|string|max:200',
		'start_date' => 'required|date_format:d/m/Y',
		'end_date'   => 'required|date_format:d/m/Y',
	];

	public function selfCheck(): void
	{
		parent::selfCheck();
		$this->assert($this->start_date < $this->end_date, 'La date de fin doit être postérieure à la date de début');
		$this->assert($this->closed === 0 || $this->closed === 1);
		$this->assert($this->closed == 1 || !isset($this->_modified['closed']), 'Il est interdit de réouvrir un exercice clôturé');

		$db = DB::getInstance();

		$this->assert($this->id_chart !== null);

		if ($this->exists()) {
			$this->assert(
				!$db->test(Transaction::TABLE, 'id_year = ? AND date < ?', $this->id(), $this->start_date->format('Y-m-d')),
				'Des mouvements de cet exercice ont une date antérieure à la date de début de l\'exercice.'
			);

			$this->assert(
				!$db->test(Transaction::TABLE, 'id_year = ? AND date > ?', $this->id(), $this->end_date->format('Y-m-d')),
				'Des mouvements de cet exercice ont une date postérieure à la date de fin de l\'exercice.'
			);
		}
	}

	public function close()
	{
		if ($this->closed) {
			throw new \LogicException('Cet exercice est déjà clôturé');
		}

		$this->set('closed', 1);
	}

	public function delete(): bool
	{
		// Ne pas supprimer un compte qui est utilisé !
		if ($count = $this->countTransactions()) {
			throw new UserException(sprintf('Cet exercice ne peut être supprimé car %d écritures y sont liées.', $count));
		}

		return parent::delete();
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

	public function openBalanceFromForm(?array $source = null): Transaction
	{
		if (null === $source) {
			$source = $_POST;
		}

		if (!isset($source['lines']) || !is_array($source['lines'])) {
			throw new UserException('Aucun contenu trouvé dans le formulaire.');
		}

		if (!isset($lines['account'], $lines['credit'], $lines['debit'])) {
			throw new UserException('Problème de contenu dans le formulaire.');
		}

		$transaction = new Transaction;
		$transaction->label = 'Balance d\'ouverture';
		$transaction->date = $this->date;
		$transaction->id_year = $this->id();

		$lines = Utils::array_transpose($source['lines']);
		$debit = $credit = 0;

		foreach ($lines as $line) {
			$line['id_account'] = @count($line['account']) ? key($line['account']) : null;
			$line = (new Line)->importForm($line);
			$transaction->addLine($line);

			$debit += $line->debit;
			$credit += $line->credit;
		}

		$line = new Line;

		if ($debit > $credit) {
			$line->debit = $debit - $credit;
		}
		else {
			$line->credit = $credit - $debit;
		}

		$line->id_account = EntityManager::findOne(Account::class, 'SELECT * FROM @TABLE WHERE id_chart = ? AND type = ? LIMIT 1;', $this->id_chart, Account::TYPE_OPENING);

		$transaction->addLine($line);

		return $transaction;
	}
}
