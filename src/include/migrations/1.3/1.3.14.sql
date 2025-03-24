CREATE INDEX IF NOT EXISTS acc_transactions_creator ON acc_transactions (id_creator);

CREATE TABLE IF NOT EXISTS acc_years_provisional
-- Provisional (prévisionnel)
(
	id_year INTEGER NOT NULL REFERENCES acc_years (id) ON DELETE CASCADE,
	id_account INTEGER NOT NULL REFERENCES acc_accounts (id) ON DELETE CASCADE,
	amount INTEGER NOT NULL
);

CREATE UNIQUE INDEX IF NOT EXISTS acc_years_provisional_id_year ON acc_years_provisional (id_year, id_account);
