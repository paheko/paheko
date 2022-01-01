<?php

namespace Garradin\Entities\Services;

use Garradin\DB;
use Garradin\Entity;
use Garradin\Membres;
use Garradin\ValidationException;
use Garradin\Services\Fees;
use Garradin\Services\Services;
use Garradin\Entities\Accounting\Transaction;

class Service_User extends Entity
{
	const TABLE = 'services_users';

	protected $id;
	protected $id_user;
	protected $id_service;
	/**
	 * This can be NULL if there is no fee for the service
	 * @var null|int
	 */
	protected $id_fee;
	protected $paid;
	protected $expected_amount;
	protected $date;
	protected $expiry_date;

	protected $_types = [
		'id'          => 'int',
		'id_user'     => 'int',
		'id_service'  => 'int',
		'id_fee'      => '?int',
		'paid'        => 'bool',
		'expected_amount' => '?int',
		'date'        => 'date',
		'expiry_date' => '?date',
	];

	protected $_service, $_fee;

	public function selfCheck(): void
	{
		$this->paid = (bool) $this->paid;
		$this->assert($this->id_service, 'Aucune activité spécifiée');
		$this->assert($this->id_user, 'Aucun membre spécifié');
		$this->assert(!$this->isDuplicate(), 'Cette activité a déjà été enregistrée pour ce membre et cette date');
	}

	public function isDuplicate(bool $using_date = true): bool
	{
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

		if (!empty($source['id_service']) && empty($source['expiry_date'])) {
			$service = $this->_service = Services::get((int) $source['id_service']);

			if (!$service) {
				throw new \LogicException('The requested service is not found');
			}

			if ($service->duration) {
				$dt = new \DateTime;
				$dt->modify(sprintf('+%d days', $service->duration));
				$this->expiry_date = $dt;
			}
			elseif ($service->end_date) {
				$this->expiry_date = $service->end_date;
			}
			else {
				$this->expiry_date = null;
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

		$transaction = new Transaction;
		$transaction->id_creator = $user_id;
		$transaction->id_year = $this->fee()->id_year;

		$source['type'] = Transaction::TYPE_REVENUE;
		$key = sprintf('account_%d_', $source['type']);
		$source[$key . '0'] = [$this->fee()->id_account => ''];
		$source[$key . '1'] = isset($source['account']) ? $source['account'] : null;

		$label = $this->service()->label;

		if ($this->fee()->label != $label) {
			$label .= ' - ' . $this->fee()->label;
		}

		$label .= sprintf(' (%s)', (new Membres)->getNom($this->id_user));

		$source['label'] = $label;

		$transaction->importFromNewForm($source);
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
			$su->date = new \DateTime;
			$su->importForm($source);
			$su->id_user = (int) $id;

			if ($su->id_fee && $su->fee()->id_account && $su->id_user) {
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