<?php

namespace Garradin\Entities\Accounting;

use Garradin\DB;
use Garradin\Entity;
use Garradin\ValidationException;
use Garradin\Utils;
use Garradin\Accounting\Accounts;

class Line extends Entity
{
	const TABLE = 'acc_transactions_lines';

	protected $id;
	protected $id_transaction;
	protected $id_account;
	protected $credit = 0;
	protected $debit = 0;
	protected $reference;
	protected $label;
	protected $reconciled = 0;
	protected $id_analytical;

	protected $_types = [
		'id'             => 'int',
		'id_transaction' => 'int',
		'id_account'     => 'int',
		'credit'         => 'int',
		'debit'          => 'int',
		'reference'      => '?string',
		'label'          => '?string',
		'reconciled'     => 'int',
		'id_analytical'  => '?int',
	];

	protected $_form_rules = [
		'id_account'     => 'required|numeric|in_table:acc_accounts,id',
		'id_analytical'  => 'numeric|in_table:acc_accounts,id',
		'reference'      => 'string|max:200',
		'label'          => 'string|max:200',
	];

	public function filterUserValue(string $type, $value, string $key)
	{
		if ($key == 'credit' || $key == 'debit') {
			$value = Utils::moneyToInteger($value);
		}
		elseif ($key == 'id_analytical' && $value == 0) {
			$value = null;
		}

		$value = parent::filterUserValue($type, $value, $key);

		return $value;
	}

	public function selfCheck(): void
	{
		parent::selfCheck();
		$this->assert($this->credit || $this->debit, 'Aucun montant au débit ou au crédit');
		$this->assert($this->credit >= 0 && $this->debit >= 0, 'Le montant ne peut être négatif');
		$this->assert(($this->credit * $this->debit) === 0 && ($this->credit + $this->debit) > 0, 'Ligne non équilibrée : crédit ou débit doit valoir zéro.');
		$this->assert($this->id_transaction, 'Aucun mouvement n\'a été indiqué pour cette ligne.');
		$this->assert($this->reconciled === 0 || $this->reconciled === 1);

		$db = DB::getInstance();
		$this->assert($db->firstColumn('SELECT 1 FROM acc_accounts a
			INNER JOIN acc_transactions t ON t.id = ?
			INNER JOIN acc_years y ON y.id = t.id_year
			WHERE a.id = ? AND a.id_chart = y.id_chart;', $this->id_transaction, $this->id_account), 'Le compte sélectionné ne correspond pas à l\'exercice');
	}

	public function asDetailsArray(): array
	{
		return [
			'Compte'    => $this->id_account ? Accounts::getSelectorLabel($this->id_account) : null,
			'Libellé'   => $this->label,
			'Référence' => $this->reference,
			'Crédit'    => Utils::money_format($this->credit),
			'Débit'     => Utils::money_format($this->debit),
		];
	}
}
