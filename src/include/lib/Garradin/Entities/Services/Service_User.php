<?php

namespace Garradin\Entities\Services;

use Garradin\Config;
use Garradin\DynamicList;
use Garradin\Entity;
use Garradin\ValidationException;
use Garradin\Utils;
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

	protected $_service;

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
				$this->expiry_date = $dt->format('Y-m-d');
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

	static public function saveFromForm(int $user_id, ?array $source = null)
	{
		if (null === $source) {
			$source = $_POST;
		}

		$su = new self;
		$su->importForm($source);
		$su->save();

		if ($su->service()->id_account && !empty($source['paid_amount'])) {
			$transaction = new Transaction;
			$transaction->id_user = $user_id;

			$source['type'] = Transaction::TYPE_REVENUE;
			$key = sprintf('account_%d_', $source['type']);
			$source[$key . '0'] = [$su->service()->id_account() => ''];
			$source[$key . '1'] = $source['account'];

			$transaction->importFromNewForm($source);
			$transaction->save();
			$transaction->updateLinkedUsers([$su->id_user]);
		}
	}
}