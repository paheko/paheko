<?php

namespace Paheko\Entities\Services;

use Paheko\DB;
use Paheko\Entity;
use Paheko\Form;
use Paheko\UserException;
use Paheko\ValidationException;
use Paheko\Services\Fees;
use Paheko\Services\Services;
use Paheko\Users\Users;
use Paheko\Accounting\Transactions;
use Paheko\Entities\Accounting\Transaction;
use Paheko\Entities\Accounting\Line;

use KD2\DB\Date;

class Subscription extends Entity
{
	const TABLE = 'services_subscriptions';

	protected ?int $id;
	protected int $id_user;
	protected int $id_service;
	/**
	 * This can be NULL if there is no fee for the service
	 * @var null|int
	 */
	protected ?int $id_fee = null;
	protected bool $paid = false;
	protected ?int $expected_amount = null;
	protected Date $date;
	protected ?Date $expiry_date = null;

	protected $_service, $_fee;

	public function selfCheck(): void
	{
		$this->assert($this->id_service, 'Aucune activité spécifiée');
		$this->assert($this->id_user, 'Aucun membre spécifié');
		$this->assert(!$this->isDuplicate(), 'Cette activité a déjà été enregistrée pour ce membre, ce tarif et cette date');

		$db = DB::getInstance();

		// don't allow an id_fee that does not match a service
		if (isset($this->id_fee)
			&& !$db->test(Fee::TABLE, 'id = ? AND id_service = ?', $this->id_fee, $this->id_service)) {
			$this->set('id_fee', null);
		}

		parent::selfCheck();
	}

	public function save(bool $selfcheck = true): bool
	{
		if (!isset($this->date)) {
			$this->set('date', new Date);
		}

		if (!isset($this->expected_amount)) {
			$this->updateExpectedAmount();
		}

		if (isset($this->expiry_date)
			&& $this->service()->isOneOff()) {
			$this->set('expiry_date', null);
		}
		elseif (!isset($this->expiry_date)) {
			$this->updateExpiryDate();
		}

		return parent::save($selfcheck);
	}

	public function isDuplicate(bool $using_date = true): bool
	{
		if (!isset($this->id_user, $this->id_service)) {
			throw new \LogicException('Entity does not define either user or service');
		}

		$params = [
			'id_user' => $this->id_user,
			'id_service' => $this->id_service,
			'id_fee' => $this->id_fee,
		];

		if ($using_date && isset($this->date)) {
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

			// This is used for creating multiple subscriptions for a user from the caisse, see Subscriptions::createFromFee
			$qty = intval($source['qty'] ?? 1);

			$this->updateExpiryDate($qty);
		}

		if (!empty($source['id_service'])) {
			if (!$service) {
				$service = $this->_service = Services::get((int) $source['id_service']);
			}
		}

		if (!empty($source['expected_amount'])) {
			$source['expected_amount'] = abs(Utils::moneyToInteger($source['expected_amount']));
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

	public function addPayment(?Session $session, ?array $source = null): Transaction
	{
		$source ??= $_POST;

		if (!$this->id_fee) {
			throw new \RuntimeException('Cannot add a payment to a subscription that is not linked to a fee');
		}

		if (!$this->fee()->id_year) {
			throw new ValidationException('Le tarif indiqué ne possède pas d\'exercice lié');
		}

		if (empty($source['amount'])) {
			throw new ValidationException('Montant non précisé');
		}

		$account = Form::getSelectorValue($source['account_selector'] ?? null);

		if (!$account) {
			throw new ValidationException('Aucune compte n\'a été sélectionné.');
		}

		$label = $this->service()->label;

		if ($this->fee()->label != $label) {
			$label .= ' - ' . $this->fee()->label;
		}

		$label .= sprintf(' (%s)', Users::getName($this->id_user));

		$transaction = Transactions::create(array_merge($source, [
			'type' => Transaction::TYPE_REVENUE,
			'label' => $label,
			'id_project' => $source['id_project'] ?? $this->fee()->id_project,
			'simple' => [Transaction::TYPE_REVENUE => [
				'credit' => [$this->fee()->id_account => null],
				'debit' => $source['account_selector'],
			]],
			'id_year' => $this->fee()->id_year,
		]));

		$transaction->setCreatorFromSession($session);
		$transaction->id_year = $this->fee()->id_year;
		$transaction->type = Transaction::TYPE_REVENUE;

		$transaction->save();
		$transaction->linkToSubscription($this->id());

		return $transaction;
	}

	public function updateExpectedAmount(): void
	{
		$fee = $this->fee();

		if ($fee && isset($this->id_user) && ($fee->amount || $fee->formula)) {
			$this->set('expected_amount', $fee->getAmountForUser($this->id_user));
		}
		else {
			$this->set('expected_amount', null);
		}
	}

	/**
	 * @param  int $qty Quantity of duration periods to create. This is for the "caisse" plugin,
	 * where you can specify a quantity for a subscription. If your service is valid for one year, and quantity
	 * is "2", then the subscription will be valid for 2 years.
	 */
	public function updateExpiryDate(int $qty = 1): void
	{
		$service = $this->service();

		if ($service->duration) {
			$dt = new Date;
			$dt->modify(sprintf('+%d days', $service->duration * $qty));
			$this->set('expiry_date', $dt);
		}
		elseif ($service->end_date) {
			if ($qty > 1) {
				throw new UserException('Il n\'est pas possible d\'inscrire plusieurs fois un membre à une activité à date fixe.');
			}

			$this->set('expiry_date', $service->end_date);
		}
		else {
			if ($qty > 1) {
				throw new UserException('Il n\'est pas possible d\'inscrire plusieurs fois un membre à une activité sans durée.');
			}

			$this->set('expiry_date', null);
		}
	}

	static public function createFromForm(array &$users, ?Session $session, bool $from_copy = false, ?array $source = null): self
	{
		$source ??= $_POST;

		$db = DB::getInstance();
		$db->begin();

		if (!count($users)) {
			throw new ValidationException('Aucun membre n\'a été sélectionné.');
		}

		$multiple_users = count($users) > 1;
		$errors = [];

		foreach ($users as $id => $name) {
			$su = new self;
			$su->date = new Date;
			$su->importForm($source);
			$su->id_user = (int) $id;

			if (empty($su->id_service)) {
				throw new ValidationException('Aucune activité n\'a été sélectionnée.');
			}

			$su->updateExpectedAmount();

			if ($su->isDuplicate($from_copy ? false : true)) {
				if ($from_copy) {
					continue;
				}
				else {
					$errors[] = $name;

					if (!$multiple_users) {
						throw new ValidationException(sprintf('%s : Cette activité a déjà été enregistrée pour ce membre et cette date', $name));
					}

					unset($users[$id]);
					continue;
				}
			}

			$su->save();

			if ($su->id_fee && $su->fee()->id_account
				&& !empty($source['amount'])
				&& !empty($source['create_payment'])) {
				try {
					$su->addPayment($session, $source);
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

		if (count($errors)) {
			$db->rollback();

			throw new ValidationException(sprintf("Les membres suivants ne pourront pas être inscrits car ils sont déjà inscrits à cette activité et à la date indiquée :\n%s\n\nValidez à nouveau le formulaire pour confirmer les inscriptions des autres membres.", implode(', ', $errors)));
		}

		$db->commit();

		return $su;
	}
}