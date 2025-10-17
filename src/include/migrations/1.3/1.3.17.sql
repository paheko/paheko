ALTER TABLE emails RENAME TO emails_old;

CREATE TABLE IF NOT EXISTS emails (
-- List of emails addresses
-- We are not storing actual email addresses here for privacy reasons
-- So that we can keep the record (for opt-out reasons) even when the
-- email address has been removed from the users table
	id INTEGER NOT NULL PRIMARY KEY,
	hash TEXT NOT NULL,
	verified INTEGER NOT NULL DEFAULT 0,
	invalid INTEGER NOT NULL DEFAULT 0,
	fail_count INTEGER NOT NULL DEFAULT 0,
	sent_count INTEGER NOT NULL DEFAULT 0,
	fail_log TEXT NULL,
	last_sent TEXT NULL,
	accepts_messages INTEGER NOT NULL DEFAULT 1,
	accepts_reminders INTEGER NOT NULL DEFAULT 1,
	accepts_mailings INTEGER NOT NULL DEFAULT 0,
	added TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO emails SELECT
	id, hash, verified, invalid, fail_count, sent_count, fail_log, last_sent,
	CASE WHEN optout = 1 THEN 0 ELSE 1 END,
	CASE WHEN optout = 1 THEN 0 ELSE 1 END,
	CASE WHEN optout = 1 THEN 0 ELSE 1 END,
	added
	FROM emails_old;

DROP TABLE emails_old;

CREATE UNIQUE INDEX IF NOT EXISTS emails_hash ON emails (hash);
