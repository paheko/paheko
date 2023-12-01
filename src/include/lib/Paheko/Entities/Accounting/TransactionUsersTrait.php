<?php

namespace Paheko\Entities\Accounting;

use KD2\DB\EntityManager;
use Paheko\Users\DynamicFields;

trait TransactionUsersTrait
{
	public function linkToUser(int $user_id, ?int $service_id = null)
	{
		$db = EntityManager::getInstance(self::class)->DB();

		return $db->preparedQuery('REPLACE INTO acc_transactions_users (id_transaction, id_user, id_service_user) VALUES (?, ?, ?);',
			$this->id(), $user_id, $service_id);
	}

	public function deleteLinkedUsers(): void
	{
		$db = EntityManager::getInstance(self::class)->DB();
		$db->delete('acc_transactions_users', 'id_transaction = ? AND id_service_user IS NULL', $this->id());
	}

	public function updateLinkedUsers(array $users): void
	{
		$users = array_values($users);

		foreach ($users as $i => $user) {
			if (!(is_int($user) || (is_string($user) && ctype_digit($user)))) {
				throw new ValidationException(sprintf('Array item #%d: "%s" is not a valid user ID', $i, $user));
			}
		}

		$db = EntityManager::getInstance(self::class)->DB();

		$db->begin();
		$this->deleteLinkedUsers();

		foreach ($users as $id) {
			$db->preparedQuery('INSERT OR IGNORE INTO acc_transactions_users (id_transaction, id_user, id_service_user) VALUES (?, ?, NULL);', $this->id(), (int)$id);
		}

		$db->commit();
	}

	public function listLinkedUsers(): array
	{
		$db = EntityManager::getInstance(self::class)->DB();
		$identity_column = DynamicFields::getNameFieldsSQL('u');
		$number_column = DynamicFields::getNumberFieldSQL('u');
		$sql = sprintf('SELECT u.id, %s AS identity, %s AS number, l.id_service_user
			FROM users u
			INNER JOIN acc_transactions_users l ON l.id_user = u.id
			WHERE l.id_transaction = ? ORDER BY id;', $identity_column, $number_column);
		return $db->get($sql, $this->id());
	}

	public function listLinkedUsersAssoc(): array
	{
		$db = EntityManager::getInstance(self::class)->DB();
		$identity_column = DynamicFields::getNameFieldsSQL('u');
		$sql = sprintf('SELECT u.id, %s AS identity, l.id_service_user
			FROM users u
			INNER JOIN acc_transactions_users l ON l.id_user = u.id
			WHERE l.id_transaction = ? AND l.id_service_user IS NULL;', $identity_column);
		return $db->getAssoc($sql, $this->id());
	}

	public function unlinkServiceUser(int $id): void
	{
		$db = EntityManager::getInstance(self::class)->DB();
		$db->delete('acc_transactions_users', 'id_transaction = ? AND id_service_user = ?', $this->id(), $id);
	}
}
