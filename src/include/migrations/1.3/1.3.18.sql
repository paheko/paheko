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
	recipient_pgp_key TEXT NULL,
	id_recipient INTEGER NULL REFERENCES emails (id) ON DELETE CASCADE,
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

DROP TABLE emails_queue_attachments;

CREATE TABLE IF NOT EXISTS emails_queue_attachments (
	id_message INTEGER NOT NULL REFERENCES emails_queue (id) ON DELETE CASCADE,
	id_file INTEGER NOT NULL REFERENCES files (id) ON DELETE CASCADE
);

CREATE UNIQUE INDEX IF NOT EXISTS emails_queue_attachments_unique ON emails_queue_attachments (id_message, id_file);

ALTER TABLE mailings RENAME TO mailings_old;

CREATE TABLE IF NOT EXISTS mailings (
	id INTEGER NOT NULL PRIMARY KEY,
	subject TEXT NOT NULL,
	preheader TEXT NULL,
	body TEXT NULL,
	sender_name TEXT NULL,
	sender_email TEXT NULL,
	sent TEXT NULL CHECK (datetime(sent) IS NULL OR datetime(sent) = sent),
	anonymous INTEGER NOT NULL DEFAULT 0
);

INSERT INTO mailings SELECT
	id,
	subject,
	NULL,
	body,
	sender_name,
	sender_email,
	sent,
	anonymous
	FROM mailings_old;

CREATE INDEX IF NOT EXISTS mailings_sent ON mailings (sent);

DROP TABLE mailings_old;

-- Remove "(voir remarque X)" from account labels
UPDATE acc_accounts SET label = TRIM(REPLACE(REPLACE(REPLACE(label, '(voir remarque D)', ''), '(voir remarque C)', ''), '(voir remarque A)', ''))
	WHERE id_chart IN (SELECT id FROM acc_charts WHERE country = 'FR' AND code = 'PCS_2018');
