-- Add last_updated column to modules
ALTER TABLE modules ADD COLUMN last_updated TEXT NULL CHECK (last_updated IS NULL OR datetime(last_updated) = last_updated);

-- Rename services_users to services_subscriptions
DROP INDEX IF EXISTS acc_transactions_users_service;
ALTER TABLE services_users RENAME TO services_subscriptions;

ALTER TABLE acc_transactions_users RENAME TO acc_transactions_users_old;

-- Rename foreign key in acc_transactions_users
CREATE TABLE IF NOT EXISTS acc_transactions_users
-- Linking transactions and users
(
	id_transaction INTEGER NOT NULL REFERENCES acc_transactions (id) ON DELETE CASCADE,
	id_user INTEGER NOT NULL REFERENCES users (id) ON DELETE CASCADE,
	id_subscription INTEGER NULL REFERENCES services_subscriptions (id) ON DELETE CASCADE,

	PRIMARY KEY (id_transaction, id_user, id_subscription)
);

INSERT INTO acc_transactions_users SELECT id_transaction, id_user, id_service_user FROM acc_transactions_users_old;
DROP TABLE acc_transactions_users_old;

CREATE INDEX IF NOT EXISTS acc_transactions_users_transaction ON acc_transactions_users (id_transaction);
CREATE INDEX IF NOT EXISTS acc_transactions_user ON acc_transactions_users (id_user);
CREATE INDEX IF NOT EXISTS acc_transactions_subscription ON acc_transactions_users (id_subscription);
CREATE UNIQUE INDEX IF NOT EXISTS acc_transactions_users_unique ON acc_transactions_users (id_user, id_transaction, COALESCE(id_subscription, 0));

-- Add column to reminders
ALTER TABLE services_reminders ADD COLUMN not_before_date TEXT NULL CHECK (date(not_before_date) IS NULL OR date(not_before_date) = not_before_date);

ALTER TABLE services_reminders_sent RENAME TO services_reminders_sent_old;

CREATE TABLE IF NOT EXISTS services_reminders_sent
-- Records of sent reminders, to keep track
(
	id INTEGER NOT NULL PRIMARY KEY,

	id_user INTEGER NOT NULL REFERENCES users (id) ON DELETE CASCADE,
	id_service INTEGER NOT NULL REFERENCES services (id) ON DELETE CASCADE,
	id_reminder INTEGER NULL REFERENCES services_reminders (id) ON DELETE SET NULL,

	sent_date TEXT NOT NULL DEFAULT CURRENT_DATE CHECK (date(sent_date) IS NOT NULL AND date(sent_date) = sent_date),
	due_date TEXT NOT NULL CHECK (date(due_date) IS NOT NULL AND date(due_date) = due_date)
);

INSERT INTO services_reminders_sent SELECT * FROM services_reminders_sent_old;
DROP TABLE services_reminders_sent_old;

CREATE UNIQUE INDEX IF NOT EXISTS srs_index ON services_reminders_sent (id_user, id_service, id_reminder, due_date);

CREATE INDEX IF NOT EXISTS srs_reminder ON services_reminders_sent (id_reminder);
CREATE INDEX IF NOT EXISTS srs_user ON services_reminders_sent (id_user);

-- Create new import_rules table
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
