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

		return $db->preparedQuery('REPLACE INTO acc_transactions_users (id_transaction, id_subscription, id_user)
			SELECT ?, id, id_user FROM services_subscriptions WHERE id = ?;',
			$this->id(),
			$id_subscription
		);
	}

	public function deleteAllSubscriptionLinks(): void
	{
		$db = EntityManager::getInstance(self::class)->DB();
		$db->delete('acc_transactions_users', 'id_transaction = ? AND id_subscription IS NOT NULL', $this->id());
	}

	public function deleteSubscriptionLink(int $id): void
	{
		$db = EntityManager::getInstance(self::class)->DB();
		$db->delete('acc_transactions_users', 'id_transaction = ? AND id_subscription = ?', $this->id(), $id);
	}

	public function listSubscriptionLinks(): array
	{
		$db = EntityManager::getInstance(self::class)->DB();
		$identity_column = DynamicFields::getNameFieldsSQL('u');
		$number_column = DynamicFields::getNumberFieldSQL('u');
		$sql = sprintf('SELECT sub.*, %s AS user_identity, %s AS user_number, l.id_subscription
			FROM users u
			INNER JOIN services_subscriptions sub ON sub.id_user = u.id
			INNER JOIN acc_transactions_users l ON l.id_subscription = sub.id
			WHERE l.id_transaction = ?;', $identity_column, $number_column);
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
			$db->preparedQuery('INSERT OR IGNORE INTO acc_transactions_users (id_transaction, id_subscription, id_user)
				SELECT ?, id, id_user FROM services_subscriptions WHERE id = ?;',
				$this->id(),
				(int)$id
			);
		}

		$db->commit();
	}
}
