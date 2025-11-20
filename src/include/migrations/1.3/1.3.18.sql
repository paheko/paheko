-- Add new column to services
ALTER TABLE services ADD COLUMN archived INTEGER NOT NULL DEFAULT 0;

UPDATE services SET archived = CASE WHEN end_date IS NOT NULL AND end_date < datetime() THEN 1 ELSE 0 END;

CREATE INDEX IF NOT EXISTS services_archived ON services (archived);

-- Create new config key
INSERT INTO config (key, value) VALUES ('show_category_in_list', 1);

-- Add status to transactions lines
ALTER TABLE acc_transactions_lines ADD COLUMN status INTEGER NOT NULL DEFAULT 0;
CREATE INDEX IF NOT EXISTS acc_transactions_lines_status ON acc_transactions_lines (status);

UPDATE acc_transactions_lines SET status = 4 WHERE id_transaction IN (SELECT id FROM acc_transactions WHERE status & 4);

-- Remove deposited status from acc_transactions table
UPDATE acc_transactions SET status = (status & ~4);

-- Remove "(voir remarque X)" from account labels
UPDATE acc_accounts SET label = TRIM(REPLACE(REPLACE(REPLACE(label, '(voir remarque D)', ''), '(voir remarque C)', ''), '(voir remarque A)', ''))
	WHERE id_chart IN (SELECT id FROM acc_charts WHERE country = 'FR' AND code = 'PCS_2018');

CREATE TABLE IF NOT EXISTS users_keys
-- Users encryption keys
(
	id INTEGER NOT NULL PRIMARY KEY,
	id_user INTEGER NOT NULL REFERENCES users (id) ON DELETE CASCADE,
	public TEXT NOT NULL,
	private TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS vaults
-- Encrypted data vaults
(
	id INTEGER NOT NULL PRIMARY KEY,
	expires TEXT NULL DEFAULT CURRENT_TIMESTAMP,
	data TEXT NOT NULL -- Encrypted data
);

CREATE TABLE IF NOT EXISTS vaults_keys
-- Encrypted vaults keys
(
	id INTEGER NOT NULL PRIMARY KEY,
	id_vault INTEGER NOT NULL REFERENCES vaults(id) ON DELETE CASCADE,
	id_keypair INTEGER NULL REFERENCES users_keys(id) ON DELETE CASCADE, -- NULL if the user is for example a script or API
	id_plugin INTEGER NULL REFERENCES plugins(id) ON DELETE CASCADE, -- Not NULL if the key is used by a plugin, then it should be deleted when the plugin is deleted
	key TEXT NOT NULL -- Encrypted vault secret key, specific to this user, as it is encrypted using their public key
);

-- Delete orphan vaults (zero linked keys)
CREATE TRIGGER IF NOT EXISTS vaults_keys_delete AFTER DELETE ON vaults_keys
BEGIN
	DELETE FROM vaults WHERE id = OLD.id_vault
		AND NOT EXISTS (SELECT id_vault FROM vaults_keys WHERE id_vault = OLD.id_vault);
END;
