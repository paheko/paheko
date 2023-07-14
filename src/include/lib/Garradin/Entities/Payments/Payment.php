<?php

namespace Garradin\Entities\Payments;

use Garradin\Entity;
use Garradin\DB;
use Garradin\Entities\Users\User;
use Garradin\Entities\Accounting\Transaction;
use Garradin\Payments\Users as PaymentsUsers;

use KD2\DB\EntityManager as EM;

class Payment extends Entity
{
	const UNIQUE_TYPE = 'unique';
	const TIF_TYPE = 'tif'; // three interest-free installments
	const MONTHLY_TYPE  = 'monthly';
	const OTHER_TYPE = 'other';
	
	const PLANNED_STATUS = 'planned';
	const AWAITING_STATUS = 'awaiting';
	const VALIDATED_STATUS = 'validated';
	const CANCELLED_STATUS = 'cancelled';
	
	const CASH_METHOD = 'cash';
	const CHEQUE_METHOD = 'cheque';
	const BANK_CARD_METHOD = 'bank_card';
	const BANK_WIRE_METHOD = 'bank_wire';
	const OTHER_METHOD = 'other';
	
	const TYPES = [ self::UNIQUE_TYPE => 'unique', self::TIF_TYPE => '3x sans frais', self::MONTHLY_TYPE => 'mensuel', self::OTHER_TYPE => 'autre'];
	const STATUSES = [ self::PLANNED_STATUS => 'planifié', self::AWAITING_STATUS => 'en attente', self::VALIDATED_STATUS => 'validé', self::CANCELLED_STATUS => 'annulé' ];
	const METHODS = [ self::CASH_METHOD => 'espèces', self::CHEQUE_METHOD => 'chèque', self::BANK_CARD_METHOD => 'carte bancaire', self::BANK_WIRE_METHOD => 'virement', self::OTHER_METHOD => 'autre'];
	
	const TABLE = 'payments';

	protected int			$id;
	protected ?string		$reference = null;
	protected ?int			$id_author = null;
	protected ?int			$id_payer = null;
	protected ?string		$payer_name = null;
	protected string		$provider;
	protected string		$type;
	protected string		$status;
	protected string		$label;
	protected int			$amount;
	protected \DateTime		$date;
	protected string		$method;
	protected string		$history = '';
	// Warning: do NOT directly set $extra_data properties (e.g., $payment->extra_data->dummy = 17) or the value will not be updated into the database while using $payment->save()
	// Instead you must use the setExtraData() method (e.g., $payment->setExtraData('dummy', 17) to enable the update trigger
	protected ?\stdClass	$extra_data = null;
	//protected int			$vat;

	public function get(string $key)
	{
		if (property_exists($this, $key)) {
			return $this->$key;
		}
		if ($this->extra_data) // extra_data may be null
		{
			if (!property_exists($this->extra_data, $key)) {
				throw new \InvalidArgumentException(sprintf('%s property does not exist neither in %s nor in its extra_data member.', $key, self::class));
			}
			return $this->extra_data->$key;
		}
		return null;
	}

	public function setExtraData(string $key, $value, bool $loose = false, bool $check_for_changes = true)
	{
		// ToDo: implements $loose option
		// ToDo: implements getAsString() as in AbstractEntity::set()
		$original_value = isset($this->extra_data->$key) ? $this->extra_data->$key : null;
		
		if (null === $this->extra_data) {
			$this->extra_data = new \stdClass();
		}
		$this->extra_data->$key = $value;
		
		if ($check_for_changes && $original_value !== $this->extra_data->$key) {
			$this->_modified['extra_data'] = clone $this->extra_data;
		}
	}

	public function bindToUsers(array $user_ids, ?array $notes = null): void
	{
		PaymentsUsers::add($this->id, $user_ids, $notes);
	}

	public function getTransactions(): array
	{
		return EM::getInstance(Transaction::class)->all('SELECT * FROM @TABLE WHERE reference = :reference', (int)$this->id);
	}

	public function selfCheck(): void
	{
		parent::selfCheck();
		$this->assert(array_key_exists($this->type, self::TYPES), sprintf('Unknown type: %s. Allowed types are: %s.', $this->type, implode(', ', array_keys(self::TYPES))));
		$this->assert(array_key_exists($this->status, self::STATUSES), sprintf('Unknown status: %s. Allowed statuses are: %s.', $this->status, implode(', ', array_keys(self::STATUSES))));
		$this->assert(array_key_exists($this->method, self::METHODS), sprintf('Unknown type: %s. Allowed types are: %s.', $this->method, implode(', ', array_keys(self::METHODS))));
		$this->assert(null === $this->id_author || DB::getInstance()->test(User::TABLE, 'id = ?', $this->id_author), 'L\'autheur/trice sélectionné.e n\'existe pas.');
	}
	
	public function addLog(string $message, ?\Datetime $date = null): void
	{
		if (null === $date) {
			$date = new \DateTime();
		}
		$this->set('history', $date->format('Y-m-d H:i:s') . ' - ' . $message . "\n" . $this->history);
	}
	
	public function validate(int $amount, ?string $receipt_url = null): bool
	{
		if ($amount != $this->amount) {
			throw new \LogicException(sprintf('Amount mismatch: paid %f != %f asked', $amount / 100, $this->amount / 100));
		}

		if (null !== $receipt_url) {
			$message = sprintf("Paiement validé (Reçu de paiement : %s).", $receipt_url);
		}
		else {
			$message = 'Paiement validé.';
		}

		return $this->updateStatus(self::VALIDATED_STATUS, $message);
	}
	
	public function updateStatus(string $status, string $message): bool
	{
		if ($status === $this->status) {
			return true;
		}

		$this->addLog($message);
		$this->set('status', $status);

		return $this->save();
	}
}
