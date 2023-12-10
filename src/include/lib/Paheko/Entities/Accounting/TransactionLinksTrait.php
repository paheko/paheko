<?php

namespace Paheko\Entities\Accounting;

use KD2\DB\EntityManager;

trait TransactionLinksTrait
{
	public function deleteLinkedTransactions(): void
	{
		$db = EntityManager::getInstance(self::class)->DB();
		$db->delete('acc_transactions_links', 'id_transaction = ? OR id_related = ?', $this->id(), $this->id());
	}

	public function updateLinkedTransactions(array $ids): void
	{
		$ids = array_values($ids);
		$ids = array_map('intval', $ids);

		$db = EntityManager::getInstance(self::class)->DB();

		$db->begin();
		$this->deleteLinkedTransactions();

		foreach ($ids as $id) {
			$db->preparedQuery('INSERT OR IGNORE INTO acc_transactions_links (id_transaction, id_related) VALUES (?, ?);', $this->id(), (int)$id);
		}

		$db->commit();
	}

	public function linkToTransaction(int $id): void
	{
		$db = EntityManager::getInstance(self::class)->DB();

		if ($db->test(self::TABLE, 'id_transaction = ? OR id_related = ?', $this->id, $this->id)) {
			return;
		}

		$params = ['id_transaction' => $this->id(), 'id_related' => $this->id()];

		$db->insert(self::TABLE, $params);
	}

	public function listLinkedTransactions()
	{
		return EntityManager::getInstance(self::class)->all('SELECT t.*
			FROM @TABLE AS t
			INNER JOIN acc_transactions_links AS l ON (l.id_transaction = t.id OR l.id_related = t.id)
			WHERE (l.id_transaction = ? OR l.id_related = ?) AND t.id != ? ORDER BY t.id;', $this->id(), $this->id(), $this->id());
	}

	public function listLinkedTransactionsAssoc()
	{
		return EntityManager::getInstance(self::class)->DB()->getAssoc('SELECT t.id, t.id
			FROM acc_transactions AS t
			INNER JOIN acc_transactions_links AS l ON (l.id_transaction = t.id OR l.id_related = t.id)
			WHERE (l.id_transaction = ? OR l.id_related = ?) AND t.id != ? GROUP BY t.id ORDER BY t.id;', $this->id(), $this->id(), $this->id());
	}
}
