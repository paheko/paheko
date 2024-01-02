<?php

namespace Paheko\Entities\Accounting;

use KD2\DB\EntityManager;
use Paheko\Users\DynamicFields;

/**
 * Manage links between service subscriptions and accounting transactions
 */
trait TransactionSubscriptionsTrait
{
	public function linkToSubscription(int $id_subscription)
	{
		$db = EntityManager::getInstance(self::class)->DB();

		return $db->preparedQuery('REPLACE INTO acc_transactions_users (id_transaction, id_user, id_service_user)
			SELECT ?, id_user, id FROM services_users WHERE id = ?;',
			$this->id(),
			$id_subscription
		);
	}

	public function deleteAllSubscriptionLinks(): void
	{
		$db = EntityManager::getInstance(self::class)->DB();
		$db->delete('acc_transactions_users', 'id_transaction = ? AND id_service_user IS NOT NULL', $this->id());
	}

	public function deleteSubscriptionLink(int $id): void
	{
		$db = EntityManager::getInstance(self::class)->DB();
		$db->delete('acc_transactions_users', 'id_transaction = ? AND id_service_user = ?', $this->id(), $id);
	}

	public function listSubscriptionLinks(): array
	{
		$db = EntityManager::getInstance(self::class)->DB();
		$identity_column = DynamicFields::getNameFieldsSQL('u');
		$number_column = DynamicFields::getNumberFieldSQL('u');
		$sql = sprintf('SELECT s.*, %s AS user_identity, %s AS user_number, l.id_service_user AS id_subscription
			FROM users u
			INNER JOIN acc_transactions_users l ON l.id_user = u.id
			INNER JOIN services_users s ON s.id = l.id_service_user
			WHERE l.id_transaction = ? AND l.id_service_user IS NOT NULL;', $identity_column, $number_column);
		return $db->get($sql, $this->id());
	}

	public function updateSubscriptionLinks(array $subscriptions): void
	{
		$subscriptions = array_values($subscriptions);

		foreach ($subscriptions as $i => $subscription) {
			if (!(is_int($subscription) || (is_string($subscription) && ctype_digit($subscription)))) {
				throw new ValidationException(sprintf('Array item #%d: "%s" is not a valid subscription ID', $i, $subscription));
			}
		}

		$db = EntityManager::getInstance(self::class)->DB();

		$db->begin();
		$this->deleteAllSubscriptionLinks();

		foreach ($subscriptions as $id) {
			$db->preparedQuery('INSERT OR IGNORE INTO acc_transactions_users (id_transaction, id_user, id_service_user)
				SELECT ?, id_user, id FROM services_users WHERE id = ?;',
				$this->id(),
				(int)$id
			);
		}

		$db->commit();
	}
}
