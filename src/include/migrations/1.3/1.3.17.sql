CREATE TABLE IF NOT EXISTS emails_addresses (
-- List of emails addresses
-- We are not storing actual email addresses here for privacy reasons
-- So that we can keep the record (for opt-out reasons) even when the
-- email address has been removed from the users table
	id INTEGER NOT NULL PRIMARY KEY,
	hash TEXT NOT NULL,
	status TEXT NULL,
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
		WHEN invalid = 1 THEN 'invalid'
		WHEN optout = 1 THEN NULL
		WHEN verified = 1 THEN 'verified'
		WHEN fail_count > 5 THEN 'failed'
		ELSE NULL
	END,
	fail_count,
	sent_count,
	last_sent,
	CASE WHEN optout = 1 THEN 0 ELSE 1 END,
	CASE WHEN optout = 1 THEN 0 ELSE 1 END,
	CASE WHEN optout = 1 THEN 0 ELSE 1 END,
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