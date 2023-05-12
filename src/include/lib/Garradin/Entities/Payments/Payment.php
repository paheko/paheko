<?php

namespace Garradin\Entities\Payments;

use Garradin\Entity;

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
	
	const TABLE = 'payment';

	protected int			$id;
	protected ?string		$reference;
	protected ?int			$id_author;
	protected string		$author_name;
	protected string		$provider;
	protected string		$type;
	protected string		$status;
	protected string		$label;
	protected int			$amount;
	protected \DateTime		$date;
	protected string		$method;
	protected string		$history;
	// Warning: do NOT directly set $extra_data properties (e.g., $payment->extra_data->dummy = 17) or the value will not be updated into the database while using $payment->save()
	// Instead you must use the setExtraData() method (e.g., $payment->setExtraData('dummy', 17) to enable the update trigger
	protected ?\stdClass	$extra_data;
	//protected int			$vat;

	public function get(string $key)
	{
		if (property_exists($this, $key)) {
			return $this->$key;
		}
		if (!property_exists($this->extra_data, $key)) {
			throw new \InvalidArgumentException(sprintf('%s property does not exist neither in %s not in its extra_data member.', $key, self::class));
		}
		return $this->extra_data->$key;
	}

	public function setExtraData(string $key, $value, bool $loose = false, bool $check_for_changes = true)
	{
		// ToDo: implements $loose option
		// ToDo: implements getAsString() as in AbstractEntity::set()
		$original_value = isset($this->extra_data->$key) ? $this->extra_data->$key : null;
		
		$this->extra_data->$key = $value;
		
		if ($check_for_changes && $original_value !== $this->extra_data->$key) {
			$this->_modified['extra_data'] = clone $this->extra_data;
		}
	}
}
