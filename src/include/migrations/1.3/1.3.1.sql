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
	target_value TEXT NOT NULL
);

CREATE UNIQUE INDEX IF NOT EXISTS mailings_optouts_unique ON mailings_optouts (email_hash, target_type, target_value);
