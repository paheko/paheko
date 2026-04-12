ALTER TABLE modules ADD COLUMN last_updated TEXT NULL CHECK (last_updated IS NULL OR datetime(last_updated) = last_updated);

CREATE TABLE IF NOT EXISTS acc_import_rules (
	id INTEGER NOT NULL PRIMARY KEY,
	label TEXT NULL,

	regexp INTEGER NOT NULL DEFAULT 0,

	match_file_name TEXT NULL,
	match_account TEXT NULL,
	match_label TEXT NULL,
	match_date TEXT NULL,
	min_amount INTEGER NULL,
	max_amount INTEGER NULL,

	target_account TEXT NULL,
	new_label TEXT NULL,
	new_reference TEXT NULL,
	new_payment_ref TEXT NULL
);

CREATE TABLE IF NOT EXISTS web_suspicious_clients
(
	ip TEXT NOT NULL,
	expiry TEXT NOT NULL CHECK (datetime(expiry) = expiry),
	UNIQUE(ip)
);

CREATE TABLE IF NOT EXISTS web_pages_uris
(
	id_page INTEGER NOT NULL REFERENCES web_pages ON DELETE CASCADE,
	uri TEXT NOT NULL,
	UNIQUE (uri)
);

CREATE TABLE acc_letters (
	id INTEGER PRIMARY KEY NOT NULL,
	id_year INTEGER NOT NULL REFERENCES acc_years(id) ON DELETE CASCADE,
	letter TEXT NOT NULL,
	created TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP CHECK (datetime(created) = created),
	UNIQUE(id_year, letter)
);

ALTER TABLE acc_transactions_lines ADD COLUMN id_letter INTEGER NULL REFERENCES acc_letters (id) ON DELETE SET NULL;

CREATE TRIGGER IF NOT EXISTS acc_transactions_lines_delete AFTER DELETE ON acc_transactions_lines
	WHEN OLD.id_letter IS NOT NULL
	BEGIN
		DELETE FROM acc_letters WHERE id = OLD.id_letter;
	END;

CREATE TRIGGER IF NOT EXISTS acc_transactions_lines_update AFTER UPDATE ON acc_transactions_lines
	WHEN OLD.id_letter IS NOT NULL AND (OLD.debit != NEW.debit
		OR OLD.id_account != NEW.id_account
		OR OLD.id_letter != NEW.id_letter
		OR NEW.id_letter IS NULL)
	BEGIN
		DELETE FROM acc_letters WHERE id = OLD.id_letter;
	END;
