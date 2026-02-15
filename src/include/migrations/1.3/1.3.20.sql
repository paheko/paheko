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
