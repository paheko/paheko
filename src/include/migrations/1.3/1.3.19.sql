-- Delete duplicate rows
DELETE FROM acc_transactions_users WHERE id_service_user IS NULL AND rowid NOT IN (
	SELECT MIN(rowid) FROM acc_transactions_users WHERE id_service_user IS NULL GROUP BY id_transaction, id_user
);

ALTER TABLE acc_transactions_users RENAME TO acc_transactions_users_old;
DROP INDEX IF EXISTS acc_transactions_users_service;
DROP INDEX IF EXISTS acc_transactions_users_unique;

-- Change foreign key to cascade
CREATE TABLE IF NOT EXISTS acc_transactions_users
-- Linking transactions and users
(
	id_user INTEGER NOT NULL REFERENCES users (id) ON DELETE CASCADE,
	id_transaction INTEGER NOT NULL REFERENCES acc_transactions (id) ON DELETE CASCADE,
	id_service_user INTEGER NULL REFERENCES services_users (id) ON DELETE CASCADE,

	PRIMARY KEY (id_user, id_transaction, id_service_user)
);

CREATE INDEX IF NOT EXISTS acc_transactions_users_service ON acc_transactions_users (id_service_user);
CREATE UNIQUE INDEX IF NOT EXISTS acc_transactions_users_unique ON acc_transactions_users (id_user, id_transaction, COALESCE(id_service_user, 0));

INSERT INTO acc_transactions_users SELECT * FROM acc_transactions_users_old;

DROP TABLE acc_transactions_users_old;
