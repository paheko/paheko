<?php

namespace Garradin\Entities\Services;

use Garradin\DB;
use Garradin\Entity;
use Garradin\ValidationException;
use Garradin\Utils;
use Garradin\Entities\Accounting\Account;
use KD2\DB\EntityManager;

class Fee extends Entity
{
	const TABLE = 'services_fees';

	protected $id;
	protected $label;
	protected $description;
	protected $amount;
	protected $formula;
	protected $id_service;
	protected $id_account;

	protected $_types = [
		'id'          => 'int',
		'label'       => 'string',
		'description' => '?string',
		'amount'      => '?int',
		'formula'     => '?string',
		'id_service'  => 'int',
		'id_account'  => '?int',
	];

	public function filterUserValue(string $type, $value, string $key)
	{
		if ($key == 'amount') {
			$value = Utils::moneyToInteger($value);
		}

		return $value;
	}

	public function importForm(array $source = null)
	{
		if (null === $source) {
			$source = $_POST;
		}

		if (isset($source['account']) && is_array($source['account'])) {
			$source['id_account'] = (int)key($source['account']);
		}

		if (isset($source['amount_type'])) {
			if ($source['amount_type'] == 2) {
				$source['amount'] = null;
			}
			elseif ($source['amount_type'] == 1) {
				$source['formula'] = null;
			}
			else {
				$source['amount'] = $source['formula'] = null;
			}
		}

		return parent::importForm($source);
	}

	public function selfCheck(): void
	{
		$db = DB::getInstance();
		parent::selfCheck();

		$this->assert(trim($this->label) !== '', 'Le libellé doit être renseigné');
		$this->assert(strlen($this->label) <= 200, 'Le libellé doit faire moins de 200 caractères');
		$this->assert(strlen($this->description) <= 2000, 'La description doit faire moins de 2000 caractères');
		$this->assert(null === $this->amount || $this->amount > 0, 'Le montant est invalide');
		$this->assert($this->id_service, 'Aucun service n\'a été indiqué pour ce tarif.');
		$this->assert(null === $this->id_account || $db->test(Account::TABLE, 'id = ?', $this->id_account), 'Le compte du plan comptable indiqué n\'existe pas');
		$this->assert(null === $this->formula || $this->checkFormula(), 'Formule de calcul invalide');
		$this->assert(null === $this->amount || null === $this->formula, 'Il n\'est pas possible de spécifier à la fois une formule et un montant');
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
