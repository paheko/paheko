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

ALTER TABLE emails_queue RENAME TO emails_queue_old;

CREATE TABLE IF NOT EXISTS emails_queue (
-- List of emails waiting to be sent
	id INTEGER NOT NULL PRIMARY KEY,
	sender TEXT NULL,
	reply_to TEXT NULL,
	recipient TEXT NOT NULL,
	recipient_hash TEXT NOT NULL,
	recipient_pgp_key TEXT NULL,
	subject TEXT NOT NULL,
	body TEXT NOT NULL,
	html_body TEXT NULL,
	added TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP CHECK (datetime(added) = added),
	status INTEGER NOT NULL DEFAULT 0, -- Will be changed to 1 when the queue run will start
	sending_started TEXT NULL CHECK (datetime(sending_started) IS NULL OR datetime(sending_started) = sending_started), -- Will be filled with the datetime when the email queue sending has started
	context INTEGER NOT NULL
);

INSERT INTO emails_queue SELECT id, sender, NULL, recipient, recipient_hash,
	recipient_pgp_key, subject, content, content_html, datetime(), sending,
	sending_started, context FROM emails_queue_old;

CREATE INDEX IF NOT EXISTS emails_queue_status ON emails_queue (status);

DROP TABLE emails_queue_old;

CREATE TABLE IF NOT EXISTS emails_addresses (
-- List of emails addresses
-- We are not storing actual email addresses here for privacy reasons
-- So that we can keep the record (for opt-out reasons) even when the
-- email address has been removed from the users table
	id INTEGER NOT NULL PRIMARY KEY,
	hash TEXT NOT NULL,
	status INTEGER NOT NULL,
	bounce_count INTEGER NOT NULL DEFAULT 0,
	sent_count INTEGER NOT NULL DEFAULT 0,
	log TEXT NULL,
	last_sent TEXT NULL,
	added TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO emails_addresses SELECT id, hash,
	CASE
		WHEN invalid = 1 THEN -3
		WHEN optout = 1 THEN -4
		WHEN verified = 1 THEN 1
		WHEN fail_count > 5 THEN -2
		ELSE 0
	END,
	fail_count, sent_count, fail_log, last_sent, added
	FROM emails;

DROP TABLE emails;

CREATE UNIQUE INDEX IF NOT EXISTS emails_hash ON emails_addresses (hash);

ALTER TABLE mailings_recipients RENAME TO mailings_recipients_old;

CREATE TABLE IF NOT EXISTS mailings_recipients (
	id INTEGER NOT NULL PRIMARY KEY,
	id_mailing INTEGER NOT NULL REFERENCES mailings (id) ON DELETE CASCADE,
	email TEXT NULL,
	id_email TEXT NULL REFERENCES emails_addresses (id) ON DELETE CASCADE,
	extra_data TEXT NULL
);

INSERT INTO mailings_recipients SELECT * FROM mailings_recipients_old;

DROP INDEX mailings_recipients_id;
CREATE INDEX IF NOT EXISTS mailings_recipients_id ON mailings_recipients (id);

DROP TABLE mailings_recipients_old;

ALTER TABLE logs RENAME TO logs_old;

-- Store user name in audit logs
ALTER TABLE logs ADD COLUMN user_name TEXT NULL;
