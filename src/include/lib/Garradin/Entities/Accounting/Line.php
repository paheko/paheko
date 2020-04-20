<?php

namespace Garradin\Entities\Accounting;

use Garradin\Entity;
use Garradin\ValidationException;

class Line extends Entity
{
	const TABLE = 'acc_transactions_lines';

	protected $id;
	protected $id_transaction;
	protected $id_account;
	protected $credit = 0;
	protected $debit = 0;
	protected $payment_reference;
	protected $reconciled;

	protected $_types = [
		'id_transaction'    => 'int',
		'id_account'        => 'int',
		'credit'            => 'int',
		'debit'             => 'int',
		'payment_reference' => '?string',
		'reconcilied'       => 'int',
	];

	protected $_validation_rules = [
		'id_transaction'    => 'required|integer|in_table:acc_transactions,id',
		'id_account'        => 'required|integer|in_table:acc_accounts,id',
		'credit'            => 'required|integer|min:0',
		'debit'             => 'required|integer|min:0',
		'payment_reference' => 'string|max:200',
		'reconcilied'       => 'int|min:0|max:1',
	];

	public function filterUserValue(string $key, $value, array $source)
	{
		$value = parent::filterUserValue($key, $value);

		if ($key == 'credit' || $key == 'debit')
		{
			if (!preg_match('/^(\d+)(?:[,.](\d{2}))?$/', $value, $match))
			{
				throw new ValidationException('Le format du montant est invalide. Format accepté, exemple : 142,02');
			}

			$value = $match[1] . sprintf('%02d', $match[2]);
		}

		return $value;
	}

	public function selfCheck()
	{
		parent::selfCheck();
		$this->assert($this->credit || $this->debit, 'Aucun montant au débit ou au crédit.');
		$this->assert(($this->credit * $this->debit) === 0 && ($this->credit + $this->debit) > 0, 'Ligne non équilibrée : crédit ou débit doit valoir zéro.');
		$this->assert($this->id_transaction, 'Aucun mouvement n\'a été indiqué pour cette ligne.');
	}
}
