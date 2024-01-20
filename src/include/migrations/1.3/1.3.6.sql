-- Delete old unmaintained plugin
DELETE FROM plugins_signals WHERE plugin = 'git_documents';
DELETE FROM plugins WHERE name = 'git_documents';

-- Fix access level of number field
UPDATE config_users_fields SET user_access_level = 1 WHERE user_access_level = 2 AND name = 'numero';

-- Update services to add archived column
ALTER TABLE services RENAME TO services_old;

CREATE TABLE IF NOT EXISTS services
-- Services types (French: cotisations)
(
	id INTEGER PRIMARY KEY NOT NULL,

	label TEXT NOT NULL,
	description TEXT NULL,

	duration INTEGER NULL CHECK (duration IS NULL OR duration > 0), -- En jours
	start_date TEXT NULL CHECK (start_date IS NULL OR date(start_date) = start_date),
	end_date TEXT NULL CHECK (end_date IS NULL OR (date(end_date) = end_date AND date(end_date) >= date(start_date))),

	archived INTEGER NOT NULL DEFAULT 0
);

INSERT INTO services
	SELECT *, CASE WHEN end_date IS NOT NULL AND end_date < datetime() THEN 1 ELSE 0 END FROM services_old;

DROP TABLE services_old;

-- Update services_reminders to add not_before_date
ALTER TABLE services_reminders RENAME TO services_reminders_old;

CREATE TABLE IF NOT EXISTS services_reminders
-- Reminders for service expiry
(
	id INTEGER NOT NULL PRIMARY KEY,
	id_service INTEGER NOT NULL REFERENCES services (id) ON DELETE CASCADE,

	delay INTEGER NOT NULL, -- Delay in days before or after expiry date

	subject TEXT NOT NULL,
	body TEXT NOT NULL,

	not_before_date TEXT NULL CHECK (date(not_before_date) IS NULL OR date(not_before_date) = not_before_date) -- Don't send reminder to users if they expire before this date
);

INSERT INTO services_reminders SELECT *, NULL FROM services_reminders_old;
DROP TABLE services_reminders_old;

ALTER TABLE services_reminders_sent RENAME TO services_reminders_sent_old;
DROP INDEX IF EXISTS srs_index;
DROP INDEX IF EXISTS srs_reminder;
DROP INDEX IF EXISTS srs_user;

-- Allow NULL for id_reminder
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

-- Rename services_users to services_subscriptions
DROP INDEX IF EXISTS acc_transactions_users_service;
ALTER TABLE services_users RENAME TO services_subscriptions;

ALTER TABLE acc_transactions_users RENAME TO acc_transactions_users_old;


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

-- Update mailings
ALTER TABLE mailings RENAME TO mailings_old;

DROP INDEX IF EXISTS mailings_sent;

CREATE TABLE IF NOT EXISTS mailings (
	id INTEGER NOT NULL PRIMARY KEY,
	subject TEXT NOT NULL,
	body TEXT NULL,
	target_type TEXT NULL,
	target_value TEXT NULL,
	target_label TEXT NULL,
	sender_name TEXT NULL,
	sender_email TEXT NULL,
	sent TEXT NULL CHECK (datetime(sent) IS NULL OR datetime(sent) = sent),
	anonymous INTEGER NOT NULL DEFAULT 0
);

CREATE INDEX IF NOT EXISTS mailings_sent ON mailings (sent);

INSERT INTO mailings (id, subject, body, sender_name, sender_email, sent, anonymous)
	SELECT id, subject, body, sender_name, sender_email, sent, anonymous
	FROM mailings_old;

DROP TABLE mailings_old;

CREATE TABLE IF NOT EXISTS mailings_optouts (
	email_hash TEXT NOT NULL,
	target_type TEXT NOT NULL,
	target_value TEXT NOT NULL,
	target_label TEXT NOT NULL
);

CREATE UNIQUE INDEX IF NOT EXISTS mailings_optouts_unique ON mailings_optouts (email_hash, target_type, target_value);
