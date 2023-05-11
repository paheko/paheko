<?php

namespace Garradin\Entities\Payments;

use Garradin\Entity;

class Provider extends Entity
{
	const TABLE = 'payment_provider';
	
	protected ?int $id;
	protected string $name;
	protected string $label;
}
