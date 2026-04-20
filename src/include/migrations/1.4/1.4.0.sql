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

-- Store user name for transaction creation
ALTER TABLE acc_transactions ADD COLUMN creator_name TEXT NULL;

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
