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

	protected int		$id;
	protected string	$reference;
	protected ?int		$id_author;
	protected string	$author_name;
	protected string	$provider;
	protected string	$type;
	protected string	$status;
	protected string	$label;
	protected int		$amount;
	protected \DateTime	$date;
	protected string	$method;
	protected string	$history;
	protected ?string	$extra_data;
	//protected int		$vat;
}
