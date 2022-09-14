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

	static public function create(int $id_account, int $credit, int $debit, ?string $label = null, ?string $reference = null): Line
	{
		$line = new self;
		$line->id_account = $id_account;
		$line->credit = $credit;
		$line->debit = $debit;
		$line->label = $label;
		$line->reference = $reference;

		return $line;
	}

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
		// We don't check that the account exists here
		// The fact that the account is in the right chart is checked in Transaction::selfCheck

		$this->assert($this->reference === null || strlen($this->reference) < 200, 'La référence doit faire moins de 200 caractères.');
		$this->assert($this->label === null || strlen($this->label) < 200, 'La référence doit faire moins de 200 caractères.');
		$this->assert($this->id_account !== null, 'Aucun compte n\'a été indiqué.');
		$this->assert($this->credit || $this->debit, 'Aucun montant au débit ou au crédit');
		$this->assert($this->credit >= 0 && $this->debit >= 0, 'Le montant ne peut être négatif');
		$this->assert(($this->credit * $this->debit) === 0 && ($this->credit + $this->debit) > 0, 'Ligne non équilibrée : crédit ou débit doit valoir zéro.');
		$this->assert($this->reconciled === 0 || $this->reconciled === 1);

		$this->assert(null === $this->id_analytical || DB::getInstance()->test(Account::TABLE, 'id = ?', $this->id_analytical), 'Le projet analytique indiqué n\'existe pas.');
		$this->assert(!empty($this->id_transaction), 'Aucune écriture n\'a été indiquée pour cette ligne.');
		parent::selfCheck();
	}

	public function asDetailsArray(): array
	{
		return [
			'Compte'    => $this->id_account ? Accounts::getCodeAndLabel($this->id_account) : null,
			'Libellé'   => $this->label,
			'Référence' => $this->reference,
			'Crédit'    => Utils::money_format($this->credit),
			'Débit'     => Utils::money_format($this->debit),
		];
	}

}
