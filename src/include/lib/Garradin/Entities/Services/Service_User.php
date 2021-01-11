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
		$this->assert($this->id || !DB::getInstance()->test(self::TABLE, 'id_user = ? AND id_service = ? AND date = ?', $this->id_user, $this->id_service, $this->date->format('Y-m-d')), 'Cette activité a déjà été enregistrée pour ce membre et cette date');
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

	public function service()
	{
		if (null === $this->_service) {
			$this->_service = Services::get($this->id_service);
		}

		return $this->_service;
	}

	public function fee()
	{
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
		$source['date'] = $this->date->format('d/m/Y');

		$transaction->importFromNewForm($source);
		$transaction->save();
		$transaction->linkToUser($this->id_user, $this->id());

		return $transaction;
	}

	static public function saveFromForm(int $user_id, ?array $source = null)
	{
		if (null === $source) {
			$source = $_POST;
		}

		$db = DB::getInstance();
		$db->begin();

		$su = new self;
		$su->date = new \DateTime;
		$su->importForm($source);

		if ($su->id_fee && $su->fee()->id_account && $su->id_user) {
			$su->expected_amount = $su->fee()->getAmountForUser($su->id_user);
		}

		$su->save();

		if ($su->id_fee && $su->fee()->id_account
			&& !empty($source['amount'])
			&& !empty($source['create_payment'])) {
			try {
				$su->addPayment($user_id, $source);
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

		$db->commit();

		return $su;
	}
}