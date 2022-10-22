<?php

namespace Garradin\Entities\Services;

use Garradin\DB;
use Garradin\Entity;
use Garradin\Membres;
use Garradin\ValidationException;
use Garradin\Services\Fees;
use Garradin\Services\Services;
use Garradin\Accounting\Transactions;
use Garradin\Entities\Accounting\Transaction;
use Garradin\Entities\Accounting\Line;

use KD2\DB\Date;

class Service_User extends Entity
{
	const TABLE = 'services_users';

	protected ?int $id;
	protected int $id_user;
	protected int $id_service;
	/**
	 * This can be NULL if there is no fee for the service
	 * @var null|int
	 */
	protected ?int $id_fee = null;
	protected bool $paid;
	protected ?int $expected_amount = null;
	protected Date $date;
	protected ?Date $expiry_date = null;

	protected $_service, $_fee;

	public function selfCheck(): void
	{
		$this->assert($this->id_service, 'Aucune activité spécifiée');
		$this->assert($this->id_user, 'Aucun membre spécifié');
		$this->assert(!$this->isDuplicate(), 'Cette activité a déjà été enregistrée pour ce membre et cette date');

		$db = DB::getInstance();
		// don't allow an id_fee that does not match a service
		if (null !== $this->id_fee && !$db->test(Fee::TABLE, 'id = ? AND id_service = ?', $this->id_fee, $this->id_service)) {
			$this->set('id_fee', null);
		}
	}

	public function isDuplicate(bool $using_date = true): bool
	{
		if (!isset($this->id_user, $this->id_service)) {
			throw new \LogicException('Entity does not define either user or service');
		}

		$params = [
			'id_user' => $this->id_user,
			'id_service' => $this->id_service,
		];

		if ($using_date) {
			$params['date'] = $this->date->format('Y-m-d');
		}

		$where = array_map(fn($k) => sprintf('%s = ?', $k), array_keys($params));
		$where = implode(' AND ', $where);

		if ($this->exists()) {
			$where .= sprintf(' AND id != %d', $this->id());
		}

		return DB::getInstance()->test(self::TABLE, $where, array_values($params));
	}

	public function importForm(?array $source = null)
	{
		if (null === $source) {
			$source = $_POST;
		}

		$service = null;

		if (!empty($source['id_service']) && empty($source['expiry_date'])) {
			$service = $this->_service = Services::get((int) $source['id_service']);

			if (!$service) {
				throw new \LogicException('The requested service is not found');
			}

			if ($service->duration) {
				$dt = new Date;
				$dt->modify(sprintf('+%d days', $service->duration));
				$this->set('expiry_date', $dt);
			}
			elseif ($service->end_date) {
				$this->set('expiry_date', $service->end_date);
			}
			else {
				$this->set('expiry_date', null);
			}
		}

		if (!empty($source['id_service'])) {
			if (!$service) {
				$service = $this->_service = Services::get((int) $source['id_service']);
			}
		}

		return parent::importForm($source);
	}

	public function service(): Service
	{
		if (null === $this->_service) {
			$this->_service = Services::get($this->id_service);
		}

		return $this->_service;
	}

	/**
	 * Returns the Fee entity linked to this subscription
	 * This can be NULL if there was no fee existing at the time of subscription
	 * (that way you can use subscriptions without fees if you want)
	 */
	public function fee(): ?Fee
	{
		if (null === $this->id_fee) {
			return null;
		}

		if (null === $this->_fee) {
			$this->_fee = Fees::get($this->id_fee);
		}

		return $this->_fee;
	}

	public function addPayment(int $user_id, ?array $source = null): Transaction
	{
		if (null === $source) {
			$source = $_POST;
		}

		if (!$this->id_fee) {
			throw new \RuntimeException('Cannot add a payment to a subscription that is not linked to a fee');
		}

		if (!$this->fee()->id_year) {
			throw new ValidationException('Le tarif indiqué ne possède pas d\'exercice lié');
		}

		if (empty($source['amount'])) {
			throw new ValidationException('Montant non précisé');
		}

		if (empty($source['account_selector']) || !is_array($source['account_selector']) || !key($source['account_selector'])) {
			throw new ValidationException('Aucune compte n\'a été sélectionné.');
		}

		$label = $this->service()->label;

		if ($this->fee()->label != $label) {
			$label .= ' - ' . $this->fee()->label;
		}

		$label .= sprintf(' (%s)', (new Membres)->getNom($this->id_user));

		$transaction = Transactions::create(array_merge($source, [
			'type' => Transaction::TYPE_REVENUE,
			'label' => $label,
			'id_analytical' => $source['id_analytical'] ?? $this->fee()->id_analytical,
			'simple' => [Transaction::TYPE_REVENUE => [
				[$this->fee()->id_account => null],
				$source['account_selector'],
			]],
			'id_year' => $this->fee()->id_year,
		]));

		$transaction->id_creator = $user_id;
		$transaction->id_year = $this->fee()->id_year;
		$transaction->type = Transaction::TYPE_REVENUE;

		$transaction->save();
		$transaction->linkToUser($this->id_user, $this->id());

		return $transaction;
	}

	static public function createFromForm(array $users, int $creator_id, bool $from_copy = false, ?array $source = null): self
	{
		if (null === $source) {
			$source = $_POST;
		}

		$db = DB::getInstance();
		$db->begin();

		if (!count($users)) {
			throw new ValidationException('Aucun membre n\'a été sélectionné.');
		}

		foreach ($users as $id => $name) {
			$su = new self;
			$su->date = new Date;
			$su->importForm($source);
			$su->id_user = (int) $id;

			if (empty($su->id_service)) {
				throw new ValidationException('Aucune activité n\'a été sélectionnée.');
			}

			if ($su->id_fee && $su->fee() && $su->fee()->id_account && $su->id_user) {
				$su->expected_amount = $su->fee()->getAmountForUser($su->id_user);
			}

			if ($su->isDuplicate($from_copy ? false : true)) {
				if ($from_copy) {
					continue;
				}
				else {
					throw new ValidationException(sprintf('%s : Cette activité a déjà été enregistrée pour ce membre et cette date', $name));
				}
			}

			$su->save();

			if ($su->id_fee && $su->fee()->id_account
				&& !empty($source['amount'])
				&& !empty($source['create_payment'])) {
				try {
					$su->addPayment($creator_id, $source);
				}
				catch (ValidationException $e) {
					if ($e->getMessage() == 'Il n\'est pas possible de créer ou modifier une écriture dans un exercice clôturé') {
						throw new ValidationException('Impossible d\'enregistrer l\'inscription : ce tarif d\'activité est lié à un exercice clôturé. Merci de modifier le tarif et choisir un autre exercice.', 0, $e);
					}
					else {
						throw $e;
					}
				}
			}
		}

		$db->commit();

		return $su;
	}
}