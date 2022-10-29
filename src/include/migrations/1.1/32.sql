ALTER TABLE acc_accounts RENAME TO acc_accounts_old;
ALTER TABLE acc_transactions_lines RENAME TO acc_transactions_lines_old;
ALTER TABLE services_fees RENAME TO services_fees_old;

.read ../../data/1.1.0_schema.sql

INSERT INTO acc_projects (id, code, label, description) SELECT id, code, label, description FROM acc_accounts_old WHERE type = 7;

-- Delete old analytical accounts
DELETE FROM acc_accounts_old AS a WHERE type = 7 OR
	(id_chart IN (SELECT id FROM acc_charts WHERE country = 'FR')
		AND code LIKE '9%'
		AND (SELECT COUNT(*) FROM acc_transactions_lines AS b WHERE b.id_account = a.id) = 0
	);

INSERT INTO acc_accounts SELECT *, CASE WHEN type IS NOT NULL THEN 1 ELSE 0 END FROM acc_accounts_old;

UPDATE acc_accounts SET type = 0;

-- Copy data to change the reference from acc_accounts to acc_projects
INSERT INTO services_fees SELECT * FROM services_fees_old;

--UPDATE acc_transactions_lines_old SET id_analytical = NULL WHERE id_analytical NOT IN (SELECT id FROM acc_projects);
INSERT INTO acc_transactions_lines SELECT * FROM acc_transactions_lines_old;

-- Cleanup
DROP TABLE acc_accounts_old;
DROP TABLE acc_transactions_lines_old;
DROP TABLE services_fees_old;