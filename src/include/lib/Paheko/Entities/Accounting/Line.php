<?php

namespace Paheko\Entities\Accounting;

use Paheko\DB;
use Paheko\Entity;
use Paheko\ValidationException;
use Paheko\Utils;
use Paheko\Accounting\Accounts;

class Line extends Entity
{
	const TABLE = 'acc_transactions_lines';

	protected ?int $id;
	protected int $id_transaction;
	protected int $id_account;
	protected int $credit = 0;
	protected int $debit = 0;
	protected ?string $reference = null;
	protected ?string $label = null;
	protected bool $reconciled = false;
	protected ?int $id_project = null;

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
		elseif ($key == 'id_project' && $value == 0) {
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
		$this->assert($this->credit + $this->debit < 100000000000, 'Le montant ne peut être supérieur à un milliard');
		$this->assert(($this->credit * $this->debit) === 0 && ($this->credit + $this->debit) > 0, 'Ligne non équilibrée : crédit ou débit doit valoir zéro.');

		$this->assert(null === $this->id_project || DB::getInstance()->test(Project::TABLE, 'id = ?', $this->id_project), 'Le projet analytique indiqué n\'existe pas.');
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
