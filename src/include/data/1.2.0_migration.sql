ALTER TABLE membres RENAME TO users;
ALTER TABLE plugins RENAME TO plugins_old;

-- References old membres table
ALTER TABLE services_users RENAME TO services_users;
ALTER TABLE services_reminders_sent RENAME TO services_reminders_sent;
ALTER TABLE acc_transactions RENAME TO acc_transactions_old;
ALTER TABLE acc_transactions_users RENAME TO acc_transactions_users_old;

.read 1.2.0_schema.sql

INSERT INTO services_users SELECT * FROM services_users_old;
INSERT INTO services_reminders_sent SELECT * FROM services_reminders_sent_old;
INSERT INTO acc_transactions SELECT * FROM acc_transactions_old;
INSERT INTO acc_transactions_users SELECT * FROM acc_transactions_users_old;

DROP TABLE services_users_old;
DROP TABLE services_reminders_sent_old;
DROP TABLE acc_transactions_old;
DROP TABLE acc_transactions_users_old;

INSERT INTO plugins_signals SELECT * FROM plugins_signaux;
INSERT INTO plugins SELECT * FROM plugins_old;

DROP TABLE plugins_signaux;
DROP TABLE plugins_old;

INSERT INTO searches SELECT * FROM recherches;
UPDATE searches SET target = 'accounting' WHERE target = 'compta';
UPDATE searches SET target = 'users' WHERE target = 'membres';

DROP TABLE recherches;