<?php

namespace Garradin\Payments;

use KD2\DB\EntityManager as EM;
use Garradin\DB;
use Garradin\Entities\Users\User;

class Users
{
	const TABLE = 'payments_users';

	static public function getForPaymentId(int $id_payment): array
	{
		$em = EM::getInstance(User::class);
		return $em->all(sprintf('
			SELECT u.*
			FROM %s pu
			INNER JOIN @TABLE u ON (u.id = pu.id_user)
			WHERE pu.id_payment = ?
		', self::TABLE), (int)$id_payment);
	}

	static public function getIds(int $id_payment): array
	{
		return DB::getInstance()->getAssoc(sprintf('SELECT id_user, id_user FROM %s WHERE id_payment = ?', self::TABLE), $id_payment);
	}

	static public function getNotesForPaymentId(int $id_payment): array
	{
		return DB::getInstance()->getAssoc(sprintf('SELECT id_user, notes FROM %s WHERE id_payment = ?', self::TABLE), $id_payment);
	}

	static function add(int $id_payment, array $user_ids, ?array $notes = null): bool
	{
		$db = DB::getInstance();
		$lines = [];
		foreach ($user_ids as $k => $id) {
			$lines[] = '(' . $db->quote((int)$id_payment) . ', ' . $db->quote((int)$id) . ', ' . (isset($notes[$k]) ? $db->quote($notes[$k]) : 'null') . ')';
		}
		// ToDo: link those users to the payment transaction as well
		return $db->exec(sprintf('INSERT INTO %s (id_payment, id_user, notes) VALUES %s;', self::TABLE, implode(', ', $lines)));
	}

	// TODO: implements remove() method as well
}
