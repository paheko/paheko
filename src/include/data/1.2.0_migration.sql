-- Already created before, so we need to drop it to migrate
DROP TABLE plugins_signals;

-- The new users table has already been created and copied
ALTER TABLE plugins RENAME TO plugins_old;
ALTER TABLE plugins_signaux RENAME TO plugins_signaux_old;

-- References old membres table
ALTER TABLE services_users RENAME TO services_users_old;
ALTER TABLE services_reminders_sent RENAME TO services_reminders_sent_old;
ALTER TABLE acc_transactions RENAME TO acc_transactions_old;
ALTER TABLE acc_transactions_users RENAME TO acc_transactions_users_old;

DROP VIEW acc_accounts_balances;

.read 1.2.0_schema.sql

INSERT INTO users_sessions SELECT * FROM membres_sessions;
DROP TABLE membres_sessions;

INSERT INTO services_users SELECT * FROM services_users_old;

INSERT INTO services_reminders_sent SELECT * FROM services_reminders_sent_old;

INSERT INTO acc_transactions SELECT * FROM acc_transactions_old;

INSERT INTO acc_transactions_users SELECT * FROM acc_transactions_users_old;

DROP TABLE services_reminders_sent_old;
DROP TABLE acc_transactions_users_old;
DROP TABLE acc_transactions_old;
DROP TABLE services_users_old;

INSERT INTO plugins SELECT * FROM plugins_old;
INSERT INTO plugins_signals SELECT * FROM plugins_signaux_old;

DROP TABLE plugins_signaux_old;
DROP TABLE plugins_old;

INSERT INTO searches SELECT * FROM recherches;
UPDATE searches SET target = 'accounting' WHERE target = 'compta';
UPDATE searches SET target = 'users' WHERE target = 'membres';

DROP TABLE recherches;

INSERT INTO config VALUES ('log_retention', 720);
INSERT INTO config VALUES ('log_anonymize', 365);

-- This is now part of the config_users_fields table
DELETE FROM config WHERE key IN ('champs_membres', 'champ_identite', 'champ_identifiant');

-- Seems that some installations had this leftover? Let's just drop it.
DROP TABLE IF EXISTS srs_old;

-- Drop membres
DROP TABLE IF EXISTS membres;
