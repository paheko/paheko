-- The new users table has already been created and copied
ALTER TABLE plugins RENAME TO plugins_old;
ALTER TABLE plugins_signaux RENAME TO plugins_signaux_old;

-- References old membres table
ALTER TABLE services_users RENAME TO services_users_old; -- Also take id_fee into account for unique key
ALTER TABLE services_reminders_sent RENAME TO services_reminders_sent_old;
ALTER TABLE acc_transactions RENAME TO acc_transactions_old;
ALTER TABLE acc_transactions_users RENAME TO acc_transactions_users_old;

-- Fix error on foreign key
ALTER TABLE acc_charts RENAME TO acc_charts_old;

ALTER TABLE emails_queue RENAME TO emails_queue_old;

DROP VIEW acc_accounts_balances;

ALTER TABLE files RENAME TO files_old;
ALTER TABLE files_contents RENAME TO files_contents_old;
ALTER TABLE web_pages RENAME TO web_pages_old;

UPDATE web_pages_old SET format = 'encrypted' WHERE format = 'skriv/encrypted';

.read schema.sql

INSERT OR IGNORE INTO web_pages
	SELECT id,
		CASE WHEN parent = '' THEN NULL ELSE parent END,
		path, 'web/' || path, uri, type, status, format, published, modified, title, content
	FROM web_pages_old;
DROP TABLE web_pages_old;

UPDATE acc_charts_old SET country = 'FR' WHERE country IS NULL;
INSERT INTO acc_charts SELECT * FROM acc_charts_old;
DROP TABLE acc_charts_old;

-- Add recipient_pgp_key column
INSERT INTO emails_queue
	SELECT id, sender, recipient, recipient_hash, NULL, subject, content, content_html, sending, sending_started, context
	FROM emails_queue_old;

DROP TABLE emails_queue_old;

INSERT INTO users_sessions SELECT * FROM membres_sessions;
DROP TABLE membres_sessions;

INSERT INTO services_users SELECT * FROM services_users_old;

INSERT INTO services_reminders_sent SELECT * FROM services_reminders_sent_old;

INSERT INTO acc_transactions
	SELECT
		id, type, status, label, notes, reference, date,
		NULL, NULL, NULL, --hash/prev_id/prev_hash
		id_year, id_creator, id_related
	FROM acc_transactions_old;

INSERT INTO acc_transactions_users SELECT * FROM acc_transactions_users_old;

DROP TABLE services_reminders_sent_old;
DROP TABLE acc_transactions_users_old;
DROP TABLE acc_transactions_old;
DROP TABLE services_users_old;

-- Remove old plugin as it cannot be uninstalled as it no longer exists
DELETE FROM plugins_old WHERE id = 'ouvertures';
DELETE FROM plugins_signaux_old WHERE plugin = 'ouvertures';

-- Delete old reservations plugin
DELETE FROM plugins_old WHERE id = 'reservations';

-- Rename plugins table columns to English
INSERT INTO plugins (name, label, description, author, author_url, version, config, enabled, menu, restrict_level, restrict_section)
	SELECT id, nom, description, auteur, url, version, config, 1, menu,
	CASE WHEN menu_condition IS NOT NULL THEN 2 ELSE NULL END,
	CASE WHEN menu_condition IS NOT NULL THEN 'users' ELSE NULL END
	FROM plugins_old;
INSERT INTO plugins_signals SELECT * FROM plugins_signaux_old;

DROP TABLE plugins_signaux_old;
DROP TABLE plugins_old;

INSERT INTO searches SELECT * FROM recherches;
UPDATE searches SET target = 'accounting' WHERE target = 'compta';
UPDATE searches SET target = 'users' WHERE target = 'membres';

DROP TABLE recherches;

INSERT INTO config VALUES ('log_retention', 365);

-- Rename config keys to english
UPDATE config SET key = 'default_category' WHERE key = 'categorie_membres';
UPDATE config SET key = 'color1' WHERE key = 'couleur1';
UPDATE config SET key = 'color2' WHERE key = 'couleur2';
UPDATE config SET key = 'country' WHERE key = 'pays';
UPDATE config SET key = 'currency' WHERE key = 'monnaie';
UPDATE config SET key = 'backup_frequency' WHERE key = 'frequence_sauvegardes';
UPDATE config SET key = 'backup_limit' WHERE key = 'nombre_sauvegardes';

UPDATE config SET key = 'org_name' WHERE key = 'nom_asso';
UPDATE config SET key = 'org_address' WHERE key = 'adresse_asso';
UPDATE config SET key = 'org_email' WHERE key = 'email_asso';
UPDATE config SET key = 'org_phone' WHERE key = 'telephone_asso';
UPDATE config SET key = 'org_web' WHERE key = 'site_asso';

-- This is now part of the config_users_fields table
DELETE FROM config WHERE key IN ('champs_membres', 'champ_identite', 'champ_identifiant');

-- Seems that some installations had this leftover? Let's just drop it.
DROP TABLE IF EXISTS srs_old;

-- Drop membres
DROP TABLE IF EXISTS membres;

