<?php

namespace Garradin\Accounting;

use Garradin\Entities\Accounting\Transaction;
use KD2\DB\EntityManager;

class Transactions
{
	static public function get(int $id)
	{
		return EntityManager::findOneById(Transaction::class, $id);
	}
}