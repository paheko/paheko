-- Delete duplicate rows
DELETE FROM acc_transactions_users WHERE id_service_user IS NULL AND rowid NOT IN (
	SELECT MIN(rowid) FROM acc_transactions_users WHERE id_service_user IS NULL GROUP BY id_transaction, id_user
);

-- Create index
CREATE UNIQUE INDEX IF NOT EXISTS acc_transactions_users_unique ON acc_transactions_users (id_user, id_transaction, COALESCE(id_service_user, 0));
