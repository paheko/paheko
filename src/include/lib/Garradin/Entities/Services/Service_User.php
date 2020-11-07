<?php

namespace Garradin\Entities\Services;

use Garradin\DB;
use Garradin\Entity;
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
	protected $date;
	protected $expiry_date;

	protected $_types = [
		'id'          => 'int',
		'id_user'     => 'int',
		'id_fee'      => 'int',
		'id_service'  => 'int',
		'paid'        => 'bool',
		'date'        => 'date',
		'expiry_date' => '?date',
	];

	protected $_service, $_fee;

	public function selfCheck(): void
	{
		$this->assert(!DB::getInstance()->test(self::TABLE, 'id_user = ? AND id_service = ? AND date = ?', $this->id_user, $this->id_service, $this->date->format('Y-m-d')), 'Cette activité a déjà été enregistrée pour ce membre et ce jour');
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
		$su->save();

		if ($su->fee()->id_account && !empty($source['amount'])) {
			$transaction = new Transaction;
			$transaction->id_creator = $user_id;
			$transaction->id_year = $su->fee()->id_year;

			$source['type'] = Transaction::TYPE_REVENUE;
			$key = sprintf('account_%d_', $source['type']);
			$source[$key . '0'] = [$su->fee()->id_account => ''];
			$source[$key . '1'] = isset($source['account']) ? $source['account'] : null;

			$source['label'] = 'Règlement activité - ' . $su->service()->label . ' - ' . $su->fee()->label;
			$source['date'] = $su->date->format('d/m/Y');

			$transaction->importFromNewForm($source);
			$transaction->save();
			$transaction->linkToUser($su->id_user, $su->id());
		}

		$db->commit();

		return $su;
	}
}