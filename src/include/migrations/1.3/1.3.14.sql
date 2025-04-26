CREATE INDEX IF NOT EXISTS acc_transactions_creator ON acc_transactions (id_creator);

CREATE TABLE IF NOT EXISTS acc_years_provisional
-- Provisional (prévisionnel)
(
	id_year INTEGER NOT NULL REFERENCES acc_years (id) ON DELETE CASCADE,
	id_account INTEGER NOT NULL REFERENCES acc_accounts (id) ON DELETE CASCADE,
	amount INTEGER NOT NULL
);

CREATE UNIQUE INDEX IF NOT EXISTS acc_years_provisional_id_year ON acc_years_provisional (id_year, id_account);

UPDATE acc_charts SET label = 'Plan comptable associatif (2018, révision 2024)' WHERE code = 'fr_pca_2018';
UPDATE acc_charts SET label = 'Plan comptable général, pour entreprises (2014, révision 2024)' WHERE code = 'fr_pcg_2014';
