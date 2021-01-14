<?php

namespace Garradin\Entities\Services;

use Garradin\Config;
use Garradin\DB;
use Garradin\DynamicList;
use Garradin\Entity;
use Garradin\ValidationException;
use Garradin\Utils;
use Garradin\Entities\Accounting\Account;
use Garradin\Entities\Accounting\Year;
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
	protected $id_year;

	protected $_types = [
		'id'          => 'int',
		'label'       => 'string',
		'description' => '?string',
		'amount'      => '?int',
		'formula'     => '?string',
		'id_service'  => 'int',
		'id_account'  => '?int',
		'id_year'     => '?int',
	];

	public function filterUserValue(string $type, $value, string $key)
	{
		if ($key == 'amount' && $value !== null) {
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

		if (empty($source['accounting'])) {
			$source['id_account'] = $source['id_year'] = null;
		}
		elseif (!empty($source['accounting']) && empty($source['id_account'])) {
			$source['id_account'] = null;
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
		$this->assert(null === $this->amount || $this->amount > 0, 'Le montant est invalide : ' . $this->amount);
		$this->assert($this->id_service, 'Aucun service n\'a été indiqué pour ce tarif.');
		$this->assert((null === $this->id_account && null === $this->id_year)
			|| (null !== $this->id_account && null !== $this->id_year), 'Le compte doit être indiqué avec l\'exercice');
		$this->assert(null === $this->id_account || $db->test(Account::TABLE, 'id = ?', $this->id_account), 'Le compte indiqué n\'existe pas');
		$this->assert(null === $this->id_year || $db->test(Year::TABLE, 'id = ?', $this->id_year), 'L\'exercice indiqué n\'existe pas');
		$this->assert(null === $this->id_account || $db->test(Account::TABLE, 'id = ? AND id_chart = (SELECT id_chart FROM acc_years WHERE id = ?)', $this->id_account, $this->id_year), 'Le compte sélectionné ne correspond pas à l\'exercice');
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
			$sql = $this->getFormulaSQL();
			$db->protectSelect(['membres' => null], $sql);
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

	public function paidUsersList(): DynamicList
	{
		$identity = Config::getInstance()->get('champ_identite');
		$columns = [
			'id_user' => [
				'select' => 'su.id_user',
			],
			'identity' => [
				'label' => 'Membre',
				'select' => 'm.' . $identity,
				'order' => sprintf('transliterate_to_ascii(m.%s) COLLATE NOCASE %%s', $identity),
			],
			'paid' => [
				'label' => 'Payé ?',
				'select' => 'su.paid',
			],
			'paid_amount' => [
				'label' => 'Montant payé',
				'select' => 'SUM(l.credit)',
			],
			'date' => [
				'label' => 'Date',
				'select' => 'su.date',
			],
		];

		$tables = 'services_users su
			INNER JOIN membres m ON m.id = su.id_user
			INNER JOIN services_fees sf ON sf.id = su.id_fee
			INNER JOIN (SELECT id, MAX(date) FROM services_users GROUP BY id_user, id_fee) AS su2 ON su2.id = su.id
			LEFT JOIN acc_transactions_users tu ON tu.id_service_user = su.id
			LEFT JOIN acc_transactions_lines l ON l.id_transaction = tu.id_transaction';
		$conditions = sprintf('su.id_fee = %d AND su.paid = 1 AND (su.expiry_date >= date() OR su.expiry_date IS NULL)
			AND m.id_categorie NOT IN (SELECT id FROM membres_categories WHERE cacher = 1)', $this->id());

		$list = new DynamicList($columns, $tables, $conditions);
		$list->groupBy('su.id_user');
		$list->orderBy('date', true);
		$list->setCount('COUNT(DISTINCT su.id_user)');
		return $list;
	}

	public function unpaidUsersList(): DynamicList
	{
		$list = $this->paidUsersList();
		$conditions = sprintf('su.id_fee = %d AND su.paid = 0 AND m.id_categorie NOT IN (SELECT id FROM membres_categories WHERE cacher = 1)', $this->id());
		$list->setConditions($conditions);
		return $list;
	}

	public function expiredUsersList(): DynamicList
	{
		$list = $this->paidUsersList();
		$conditions = sprintf('su.id_fee = %d AND su.expiry_date < date() AND m.id_categorie NOT IN (SELECT id FROM membres_categories WHERE cacher = 1)', $this->id());
		$list->setConditions($conditions);
		return $list;
	}
}
