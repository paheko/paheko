<?php

namespace Garradin\Entities\Services;

use Garradin\Entity;
use Garradin\ValidationException;
use Garradin\Utils;

class Fee extends Entity
{
	const TABLE = 'services_fees';

	protected $id;
	protected $label;
	protected $description;
	protected $duration;
	protected $start_date;
	protected $end_date;

	protected $_types = [
		'id'          => 'int',
		'label'       => 'string',
		'description' => '?string',
		'amount'      => '?int',
		'formula'     => '?string',
		'id_service'  => 'int',
		'id_account'  => '?int',
	];

	protected $_form_rules = [
		'label'       => 'string|max:200|required',
		'description' => 'string|max:2000',
		'amount'      => 'money',
		'formula'     => 'string',
		'id_account'  => 'integer|in_table:acc_accounts,id',
	];

	public function filterUserValue(string $type, $value, string $key)
	{
		if ($key == 'amount')
		{
			$value = Utils::moneyToInteger($value);
		}

		return $value;
	}

	public function selfCheck(): void
	{
		parent::selfCheck();
		$this->assert($this->id_service, 'Aucun service n\'a été indiqué pour ce tarif.');
		$this->assert(null === $this->formula || null === $this->amount, 'Il n\'est pas possible d\'indiquer une formule et un montant de tarif en même temps');
		$this->assert(null === $this->formula || $this->checkFormula(), 'Formule de calcul invalide');
	}

	public function getAmountForUser(int $user_id): ?int
	{
		if ($this->amount) {
			return $this->amount;
		}
		elseif ($this->formula) {
			$db = DB::getInstance();
			return (int) $db->firstColumn($this->getFormulaSQL(), $user_id);
		}

		return null;
	}

	protected function getFormulaSQL()
	{
		return sprintf('SELECT %s FROM membres WHERE id = ?;', $this->formula);
	}

	protected function checkFormula()
	{
		try {
			$db = DB::getInstance();
			$db->firstColumn($this->getFormulaSQL(), 0);
			return true;
		}
		catch (\Exception $e) {
			return false;
		}
	}

	public function service()
	{
		return EntityManager::findOneById(Service::class, $this->id_service);
	}
}
