-- Delete old unmaintained plugin
DELETE FROM plugins WHERE name = 'git_documents';

-- Fix access level of number field
UPDATE config_users_fields SET user_access_level = 1 WHERE user_access_level = 2 AND name = 'numero';

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

CREATE INDEX IF NOT EXISTS srs_reminder ON services_reminders_sent (id_reminder);
CREATE INDEX IF NOT EXISTS srs_user ON services_reminders_sent (id_user);

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
