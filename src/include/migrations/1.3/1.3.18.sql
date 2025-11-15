INSERT INTO config (key, value) VALUES ('show_category_in_list', 1);

ALTER TABLE emails_queue RENAME TO emails_queue_old;

CREATE TABLE IF NOT EXISTS emails_queue (
-- List of emails waiting to be sent
	id INTEGER NOT NULL PRIMARY KEY,
	id_mailing INTEGER NULL REFERENCES mailings (id) ON DELETE CASCADE,
	context INTEGER NOT NULL,
	status INTEGER NOT NULL,
	added TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP CHECK (datetime(added) = added),
	modified TEXT NULL DEFAULT CURRENT_TIMESTAMP CHECK (modified IS NULL OR datetime(modified) = modified),
	sender TEXT NULL,
	reply_to TEXT NULL,
	recipient TEXT NOT NULL,
	recipient_name TEXT NULL,
	recipient_pgp_key TEXT NULL,
	id_recipient INTEGER NULL REFERENCES emails_addresses (id) ON DELETE CASCADE,
	id_user INTEGER NULL REFERENCES users (id) ON DELETE CASCADE,
	subject TEXT NOT NULL,
	body TEXT NOT NULL,
	body_html TEXT NULL,
	headers TEXT NULL
);

INSERT INTO emails_queue SELECT
	id,
	NULL,
	context,
	sending,
	datetime(),
	sending_started,
	sender,
	NULL,
	recipient,
	NULL,
	recipient_pgp_key,
	NULL,
	NULL,
	subject,
	content,
	content_html,
	NULL
	FROM emails_queue_old;

DROP TABLE emails_queue_old;

CREATE INDEX IF NOT EXISTS emails_queue_status ON emails_queue (status);


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
	last_sent TEXT NULL,
	accepts_messages INTEGER NOT NULL DEFAULT 1,
	accepts_reminders INTEGER NOT NULL DEFAULT 1,
	accepts_mailings INTEGER NOT NULL DEFAULT 0,
	added TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO emails_addresses SELECT id,
	hash,
	CASE
		WHEN invalid = 1 THEN -3
		WHEN verified = 1 THEN 1
		WHEN fail_count > 5 THEN -2
		ELSE 0
	END,
	fail_count,
	sent_count,
	last_sent,
	accepts_messages,
	accepts_reminders,
	accepts_mailings,
	added
	FROM emails;

CREATE UNIQUE INDEX IF NOT EXISTS emails_hash ON emails_addresses (hash);

CREATE TABLE IF NOT EXISTS emails_addresses_events (
-- Events for each email address (message sent, bounce, optout, etc.)
	id INTEGER NOT NULL PRIMARY KEY,
	id_email INTEGER NOT NULL REFERENCES emails_addresses(id) ON DELETE CASCADE,
	date TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP CHECK (datetime(date) IS NOT NULL AND datetime(date) = date),
	type TEXT NULL,
	details TEXT NULL,
	details_encoded TEXT NULL -- JSON details for when consent has been granted or removed
);

CREATE INDEX IF NOT EXISTS emails_addresses_events_id ON emails_addresses_events (id_email);

-- Transfer of email logs to new table will be done in PHP
--DROP TABLE emails; -- Deleting this table will also happen in PHP

-- Don't migrate attachments, just delete, as they're not used currently
DROP TABLE emails_queue_attachments;

CREATE TABLE IF NOT EXISTS emails_queue_attachments (
	id_message INTEGER NOT NULL REFERENCES emails_queue (id) ON DELETE CASCADE,
	id_file INTEGER NOT NULL REFERENCES files (id) ON DELETE CASCADE
);

CREATE UNIQUE INDEX IF NOT EXISTS emails_queue_attachments_unique ON emails_queue_attachments (id_message, id_file);

ALTER TABLE mailings RENAME TO mailings_old;

DROP INDEX IF EXISTS mailings_sent;

CREATE TABLE IF NOT EXISTS mailings (
	id INTEGER NOT NULL PRIMARY KEY,
	subject TEXT NOT NULL,
	preheader TEXT NULL,
	body TEXT NULL,
	sender_name TEXT NULL,
	sender_email TEXT NULL,
	sent TEXT NULL CHECK (datetime(sent) IS NULL OR datetime(sent) = sent),
	anonymous INTEGER NOT NULL DEFAULT 0,
	pixel_count INTEGER NOT NULL DEFAULT 0
);

INSERT INTO mailings SELECT
	id,
	subject,
	NULL,
	body,
	sender_name,
	sender_email,
	sent,
	anonymous,
	0
	FROM mailings_old;

CREATE INDEX IF NOT EXISTS mailings_sent ON mailings (sent);

DROP TABLE mailings_old;

ALTER TABLE mailings_recipients RENAME TO mailings_recipients_old;

CREATE TABLE IF NOT EXISTS mailings_recipients (
	id INTEGER NOT NULL PRIMARY KEY,
	id_mailing INTEGER NOT NULL REFERENCES mailings (id) ON DELETE CASCADE,
	id_user INTEGER NULL REFERENCES users (id) ON DELETE CASCADE,
	email TEXT NOT NULL,
	id_email TEXT NULL REFERENCES emails_addresses (id) ON DELETE CASCADE,
	extra_data TEXT NULL
);

SELECT id, id_mailing, NULL, email, id_email, extra_data FROM mailings_recipients_old;

CREATE INDEX IF NOT EXISTS mailings_recipients_id ON mailings_recipients (id);

DROP TABLE mailings_recipients_old;

-- Remove "(voir remarque X)" from account labels
UPDATE acc_accounts SET label = TRIM(REPLACE(REPLACE(REPLACE(label, '(voir remarque D)', ''), '(voir remarque C)', ''), '(voir remarque A)', ''))
	WHERE id_chart IN (SELECT id FROM acc_charts WHERE country = 'FR' AND code = 'PCS_2018');
