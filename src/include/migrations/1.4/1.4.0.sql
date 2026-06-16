-- Seems that this table might exist in some cases, shouldn't be the case
DROP TABLE IF EXISTS config_users_fields_old;

-- Add last_updated column to modules
ALTER TABLE modules ADD COLUMN last_updated TEXT NULL CHECK (last_updated IS NULL OR datetime(last_updated) = last_updated);

ALTER TABLE modules ADD COLUMN version TEXT NULL;
ALTER TABLE modules ADD COLUMN db_version TEXT NULL;

CREATE TABLE modules_tables (
	id INTEGER NOT NULL PRIMARY KEY,
	id_module INTEGER NOT NULL REFERENCES modules (id) ON DELETE CASCADE,
	name TEXT NOT NULL,
	comment TEXT NULL,
	columns TEXT NOT NULL,
	UNIQUE (id_module, name)
);

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

-- Delete old unmaintained plugin
DELETE FROM plugins_signals WHERE plugin = 'git_documents';
DELETE FROM plugins WHERE name = 'git_documents';

-- Add column to reminders
ALTER TABLE services_reminders ADD COLUMN not_before_date TEXT NULL CHECK (date(not_before_date) IS NULL OR date(not_before_date) = not_before_date);


CREATE TABLE IF NOT EXISTS services_reminders_sent_tmp
-- Records of sent reminders, to keep track
(
	id INTEGER NOT NULL PRIMARY KEY,

	id_user INTEGER NOT NULL REFERENCES users (id) ON DELETE CASCADE,
	id_service INTEGER NOT NULL REFERENCES services (id) ON DELETE CASCADE,
	id_reminder INTEGER NULL REFERENCES services_reminders (id) ON DELETE SET NULL,

	sent_date TEXT NOT NULL DEFAULT CURRENT_DATE CHECK (date(sent_date) IS NOT NULL AND date(sent_date) = sent_date),
	due_date TEXT NOT NULL CHECK (date(due_date) IS NOT NULL AND date(due_date) = due_date)
);

INSERT INTO services_reminders_sent_tmp SELECT * FROM services_reminders_sent;

DROP TABLE services_reminders_sent;
ALTER TABLE services_reminders_sent_tmp RENAME TO services_reminders_sent;

CREATE UNIQUE INDEX IF NOT EXISTS srs_index ON services_reminders_sent (id_user, id_service, id_reminder, due_date);

CREATE INDEX IF NOT EXISTS srs_reminder ON services_reminders_sent (id_reminder);
CREATE INDEX IF NOT EXISTS srs_user ON services_reminders_sent (id_user);

-- Rename services_users to services_subscriptions
DROP INDEX IF EXISTS acc_transactions_users_service;
ALTER TABLE services_users RENAME TO services_subscriptions;

-- Rename foreign key in acc_transactions_users
CREATE TABLE IF NOT EXISTS acc_transactions_users_tmp
-- Linking transactions and users
(
	id_transaction INTEGER NOT NULL REFERENCES acc_transactions (id) ON DELETE CASCADE,
	id_user INTEGER NOT NULL REFERENCES users (id) ON DELETE CASCADE,
	id_subscription INTEGER NULL REFERENCES services_subscriptions (id) ON DELETE CASCADE,

	PRIMARY KEY (id_transaction, id_user, id_subscription)
);

INSERT INTO acc_transactions_users_tmp SELECT id_transaction, id_user, id_service_user FROM acc_transactions_users;
DROP TABLE acc_transactions_users;

ALTER TABLE acc_transactions_users_tmp RENAME TO acc_transactions_users;

CREATE INDEX IF NOT EXISTS acc_transactions_users_transaction ON acc_transactions_users (id_transaction);
CREATE INDEX IF NOT EXISTS acc_transactions_user ON acc_transactions_users (id_user);
CREATE INDEX IF NOT EXISTS acc_transactions_subscription ON acc_transactions_users (id_subscription);
CREATE UNIQUE INDEX IF NOT EXISTS acc_transactions_users_unique ON acc_transactions_users (id_user, id_transaction, COALESCE(id_subscription, 0));

-- Add columns to audit table
DROP TRIGGER IF EXISTS users_delete_logs;

CREATE TABLE IF NOT EXISTS logs_tmp
-- Logged events
(
	id INTEGER NOT NULL PRIMARY KEY,
	id_user INTEGER NULL REFERENCES users (id) ON DELETE SET NULL, -- The user who is responsible for the action
	user_name TEXT NULL, -- The name of the user responsible for the action, at the time of the action
	user_ip TEXT NULL,
	created TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP CHECK (datetime(created) IS NOT NULL AND datetime(created) = created),

	action INTEGER NOT NULL, -- Event action (type)
	entity TEXT NULL, -- Entity class name
	id_entity INTEGER NULL, -- Entity ID
	id_linked_user INTEGER NULL, -- The user that is being affected by the action (eg. it is being modified, added, a subscription is added, etc.)
	details TEXT NULL -- Optional details (JSON object)
);

INSERT INTO logs_tmp SELECT
	id,
	id_user,
	NULL,
	ip_address,
	created,
	type,
	json_extract(details, '$.entity'),
	CASE WHEN json_extract(details, '$.entity') IS NOT NULL THEN json_extract(details, '$.id') ELSE NULL END,
	NULL, -- NULL, as the user might not exist anymore, or it might be another user
	CASE WHEN json_extract(details, '$.entity') IS NOT NULL THEN NULL ELSE details END
FROM logs;

DROP TABLE logs;
ALTER TABLE logs_tmp RENAME TO logs;

CREATE INDEX IF NOT EXISTS logs_ip ON logs (user_ip, action, created);
CREATE INDEX IF NOT EXISTS logs_user ON logs (id_user, action, created);
CREATE INDEX IF NOT EXISTS logs_created ON logs (created);

-- Delete old triggers that might still exist
DROP TRIGGER IF EXISTS acc_transactions_chart_update;
DROP TRIGGER IF EXISTS acc_transactions_chart_insert;

-- Delete view as we need to re-create it
DROP VIEW IF EXISTS acc_accounts_balances;

-- Add AUTOINCREMENT to id, add creator_name column
CREATE TABLE IF NOT EXISTS acc_transactions_new
-- Transactions (écritures comptables)
(
	id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,

	type INTEGER NOT NULL DEFAULT 0, -- Transaction type, zero is advanced
	status INTEGER NOT NULL DEFAULT 0, -- Status (bitmask)

	label TEXT NOT NULL,
	notes TEXT NULL,
	reference TEXT NULL, -- N° de pièce comptable

	date TEXT NOT NULL DEFAULT CURRENT_DATE CHECK (date(date) IS NOT NULL AND date(date) = date),

	hash TEXT NULL,
	prev_id INTEGER NULL REFERENCES acc_transactions(id) ON DELETE SET NULL,
	prev_hash TEXT NULL,

	id_year INTEGER NOT NULL REFERENCES acc_years(id),
	id_creator INTEGER NULL REFERENCES users(id) ON DELETE SET NULL,
	creator_name TEXT NULL
);

INSERT INTO acc_transactions_new SELECT *, NULL FROM acc_transactions;
DROP TABLE acc_transactions;

ALTER TABLE acc_transactions_new RENAME TO acc_transactions;

CREATE INDEX IF NOT EXISTS acc_transactions_year ON acc_transactions (id_year);
CREATE INDEX IF NOT EXISTS acc_transactions_date ON acc_transactions (date);
CREATE INDEX IF NOT EXISTS acc_transactions_type ON acc_transactions (type, id_year);
CREATE INDEX IF NOT EXISTS acc_transactions_status ON acc_transactions (status);
CREATE INDEX IF NOT EXISTS acc_transactions_hash ON acc_transactions (hash);
CREATE INDEX IF NOT EXISTS acc_transactions_reference ON acc_transactions (reference);
CREATE INDEX IF NOT EXISTS acc_transactions_creator ON acc_transactions (id_creator);

CREATE TRIGGER IF NOT EXISTS acc_transactions_lines_delete AFTER DELETE ON acc_transactions_lines
	WHEN OLD.id_letter IS NOT NULL
	BEGIN
		DELETE FROM acc_letters WHERE id = OLD.id_letter;
	END;

CREATE TRIGGER IF NOT EXISTS acc_transactions_lines_update AFTER UPDATE ON acc_transactions_lines
	WHEN OLD.id_letter IS NOT NULL AND (OLD.debit != NEW.debit
		OR OLD.id_account != NEW.id_account
		OR OLD.id_letter != NEW.id_letter
		OR NEW.id_letter IS NULL)
	BEGIN
		DELETE FROM acc_letters WHERE id = OLD.id_letter;
	END;

-- Balance des comptes par exercice
CREATE VIEW IF NOT EXISTS acc_accounts_balances
AS
	SELECT id_year, id, label, code, type, debit, credit, bookmark,
		CASE -- 3 = dynamic asset or liability depending on balance
			WHEN position = 3 AND (debit - credit) > 0 THEN 1 -- 1 = Asset (actif) comptes fournisseurs, tiers créditeurs
			WHEN position = 3 THEN 2 -- 2 = Liability (passif), comptes clients, tiers débiteurs
			ELSE position
		END AS position,
		CASE
			WHEN position IN (1, 4) -- 1 = asset, 4 = expense
				OR (position = 3 AND (debit - credit) > 0)
			THEN
				debit - credit
			ELSE
				credit - debit
		END AS balance,
		CASE WHEN debit - credit > 0 THEN 1 ELSE 0 END AS is_debt
	FROM (
		SELECT t.id_year, a.id, a.label, a.code, a.position, a.type, a.bookmark,
			SUM(l.credit) AS credit,
			SUM(l.debit) AS debit
		FROM acc_accounts a
		INNER JOIN acc_transactions_lines l ON l.id_account = a.id
		INNER JOIN acc_transactions t ON t.id = l.id_transaction
		GROUP BY t.id_year, a.id
	);

-- Add column list_order to web_pages table
CREATE TABLE IF NOT EXISTS web_pages_tmp
(
	id INTEGER NOT NULL PRIMARY KEY,
	id_parent INTEGER NULL REFERENCES web_pages_tmp(id) ON DELETE CASCADE,
	uri TEXT NOT NULL, -- Page identifier
	type INTEGER NOT NULL, -- 1 = Category, 2 = Page
	status INTEGER NOT NULL,
	inherited_status INTEGER NOT NULL,
	format TEXT NOT NULL,
	list_order INTEGER NOT NULL,
	published TEXT NOT NULL CHECK (datetime(published) IS NOT NULL AND datetime(published) = published),
	modified TEXT NOT NULL CHECK (datetime(modified) IS NOT NULL AND datetime(modified) = modified),
	title TEXT NOT NULL,
	content TEXT NOT NULL
);

INSERT INTO web_pages_tmp SELECT
	id,
	id_parent,
	uri,
	type,
	status,
	inherited_status,
	format,
	'date',
	published,
	modified,
	title,
	content
	FROM web_pages;

DROP TABLE web_pages;
ALTER TABLE web_pages_tmp RENAME TO web_pages;

CREATE UNIQUE INDEX IF NOT EXISTS web_pages_uri ON web_pages (uri);
CREATE INDEX IF NOT EXISTS web_pages_id_parent ON web_pages (id_parent);
CREATE INDEX IF NOT EXISTS web_pages_published ON web_pages (published);
CREATE INDEX IF NOT EXISTS web_pages_title ON web_pages (title);

-- Fix web_pages_versions table (invalid foreign key)
DELETE FROM web_pages_versions WHERE id_page NOT IN (SELECT id FROM web_pages);

CREATE TABLE IF NOT EXISTS web_pages_versions_tmp
(
	id INTEGER NOT NULL PRIMARY KEY,
	id_page INTEGER NOT NULL REFERENCES web_pages (id) ON DELETE CASCADE,
	id_user INTEGER NULL REFERENCES users (id) ON DELETE SET NULL,
	date TEXT NOT NULL CHECK (datetime(date) IS NOT NULL AND datetime(date) = date),
	size INTEGER NOT NULL,
	changes INTEGER NOT NULL,
	content TEXT NOT NULL
);

INSERT INTO web_pages_versions_tmp SELECT * FROM web_pages_versions;

DROP TABLE web_pages_versions;
ALTER TABLE web_pages_versions_tmp RENAME TO web_pages_versions;

CREATE INDEX IF NOT EXISTS web_pages_versions_id_page ON web_pages_versions (id_page);

-- Better index for reconciled status
DROP INDEX IF EXISTS acc_transactions_lines_reconciled;
CREATE INDEX IF NOT EXISTS acc_transactions_lines_reconciled ON acc_transactions_lines (id_account, reconciled);
