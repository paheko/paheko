<?php

namespace Garradin\Entities\Accounting;

use KD2\DB\EntityManager;
use Garradin\DB;
use Garradin\Entity;
use Garradin\UserException;
use Garradin\Utils;
use Garradin\Accounting\Accounts;
use Garradin\Files\Files;
use Garradin\Entities\Files\File;

class Year extends Entity
{
	const NAME = 'Exercice';
	const PRIVATE_URL = '!acc/years/reports/graphs.php?year=%d';

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

	public function selfCheck(): void
	{
		$this->assert(trim($this->label) !== '', 'Le libellé ne peut rester vide.');
		$this->assert(strlen($this->label) <= 200, 'Le libellé ne peut faire plus de 200 caractères.');
		$this->assert($this->start_date instanceof \DateTime, 'La date de début de l\'exercice n\'est pas définie.');
		$this->assert($this->end_date instanceof \DateTime, 'La date de début de l\'exercice n\'est pas définie.');

		$this->assert($this->start_date < $this->end_date, 'La date de fin doit être postérieure à la date de début');
		$this->assert($this->closed === 0 || $this->closed === 1);

		$db = DB::getInstance();

		$this->assert($this->id_chart !== null);
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

	public function close(int $user_id): void
	{
		if ($this->closed) {
			throw new \LogicException('Cet exercice est déjà clôturé');
		}

		$this->set('closed', 1);
		$this->save();
	}

	public function reopen(int $user_id): void
	{
		if (!$this->closed) {
			throw new \LogicException('This year is already open');
		}

		$closing_id = $this->accounts()->getClosingAccountId();

		if (!$closing_id) {
			throw new UserException('Aucun compte n\'est indiqué comme compte de clôture dans le plan comptable');
		}

		$this->set('closed', 0);
		$this->save();

		// Create validated transaction to show that someone has reopened the year
		$t = new Transaction;
		$t->import([
			'id_year'    => $this->id(),
			'label'      => sprintf('Exercice réouvert le %s', date('d/m/Y à H:i:s')),
			'type'       => Transaction::TYPE_ADVANCED,
			'date'       => $this->end_date->format('d/m/Y'),
			'id_creator' => $user_id,
			'validated'  => 1,
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

		$t->save();
	}

	/**
	 * Splits an accounting year between the current year and another one, at a given date
	 * Any transaction after the given date will be moved to the target year.
	 */
	public function split(\DateTime $date, Year $target): void
	{
		if ($this->closed) {
			throw new \LogicException('Cet exercice est déjà clôturé');
		}

		if ($target->closed) {
			throw new \LogicException('L\'exercice cible est déjà clôturé');
		}

		DB::getInstance()->preparedQuery('UPDATE acc_transactions SET id_year = ? WHERE id_year = ? AND date > ?;',
			$target->id(), $this->id(), $date->format('Y-m-d'));
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
}
