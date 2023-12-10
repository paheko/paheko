CREATE TABLE IF NOT EXISTS acc_transactions_links
(
	id_transaction INTEGER NOT NULL REFERENCES acc_transactions(id) ON DELETE CASCADE,
	id_related INTEGER NOT NULL REFERENCES acc_transactions(id) ON DELETE CASCADE CHECK (id_transaction != id_related),
	PRIMARY KEY (id_transaction, id_related)
);

CREATE INDEX IF NOT EXISTS acc_transactions_lines_id_transaction ON acc_transactions_links (id_transaction);
CREATE INDEX IF NOT EXISTS acc_transactions_lines_id_related ON acc_transactions_links (id_related);

INSERT INTO acc_transactions_links SELECT id, id_related FROM acc_transactions WHERE id_related IS NOT NULL;

DROP INDEX acc_transactions_related;
ALTER TABLE acc_transactions RENAME TO acc_transactions_old;

CREATE TABLE IF NOT EXISTS acc_transactions
-- Transactions (écritures comptables)
(
	id INTEGER PRIMARY KEY NOT NULL,

	type INTEGER NOT NULL DEFAULT 0, -- Transaction type, zero is advanced
	status INTEGER NOT NULL DEFAULT 0, -- Status (bitmask)

	label TEXT NOT NULL,
	notes TEXT NULL,
	reference TEXT NULL, -- N° de pièce comptable

	date TEXT NOT NULL DEFAULT CURRENT_DATE CHECK (date(date) IS NOT NULL AND date(date) = date),

	hash TEXT NULL,
	prev_id INTEGER NULL REFERENCES acc_transactions(id) ON DELETE SET NULL,
	prev_hash TEXT NULL,

	id_year INTEGER NOT NULL REFERENCES acc_years(id),
	id_creator INTEGER NULL REFERENCES users(id) ON DELETE SET NULL
);

CREATE INDEX IF NOT EXISTS acc_transactions_year ON acc_transactions (id_year);
CREATE INDEX IF NOT EXISTS acc_transactions_date ON acc_transactions (date);
CREATE INDEX IF NOT EXISTS acc_transactions_type ON acc_transactions (type, id_year);
CREATE INDEX IF NOT EXISTS acc_transactions_status ON acc_transactions (status);
CREATE INDEX IF NOT EXISTS acc_transactions_hash ON acc_transactions (hash);
CREATE INDEX IF NOT EXISTS acc_transactions_reference ON acc_transactions (reference);

INSERT INTO acc_transactions 