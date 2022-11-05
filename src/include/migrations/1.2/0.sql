ALTER TABLE acc_accounts RENAME TO acc_accounts_old;
ALTER TABLE acc_transactions_lines RENAME TO acc_transactions_lines_old;
ALTER TABLE services_fees RENAME TO services_fees_old;

.read ../../data/1.2.0_schema.sql

INSERT OR IGNORE INTO acc_projects (code, label, description)
	SELECT
		a.code,
		a.label,
		a.description
	FROM acc_accounts_old a
	WHERE a.type = 7;

-- Copy data to change the column name from acc_accounts to acc_projects
INSERT INTO services_fees SELECT * FROM services_fees_old;
INSERT INTO acc_transactions_lines SELECT * FROM acc_transactions_lines_old;

-- Update references to analytical accounts
UPDATE services_fees AS a
	SET id_project = (SELECT b.id FROM acc_projects AS b INNER JOIN acc_accounts_old c ON c.code = b.code WHERE c.id = a.id_project)
	WHERE id_project IS NOT NULL;

UPDATE acc_transactions_lines AS a
	SET id_project = (SELECT b.id FROM acc_projects AS b INNER JOIN acc_accounts_old c ON c.code = b.code WHERE c.id = a.id_project)
	WHERE id_project IS NOT NULL;

-- Remove first 99 and 9 from code (added in 1.1.30)
UPDATE acc_projects SET code = CASE WHEN SUBSTR(code, 1, 2) = '99' AND LENGTH(code) > 2 THEN SUBSTR(code, 3) ELSE code END;
UPDATE acc_projects SET code = CASE WHEN SUBSTR(code, 1, 1) = '9' AND LENGTH(code) > 1 THEN SUBSTR(code, 2) ELSE code END;

UPDATE acc_transactions_lines SET id_project = NULL WHERE id_project NOT IN (SELECT id FROM acc_projects);

INSERT INTO acc_accounts SELECT *, CASE WHEN type > 0 AND type <= 8 THEN 1 ELSE 0 END FROM acc_accounts_old;

-- Delete old analytical accounts
DELETE FROM acc_accounts AS a WHERE type = 7 OR
	(id_chart IN (SELECT id FROM acc_charts WHERE country = 'FR')
		AND type != 7
		AND code LIKE '9%'
		AND (SELECT COUNT(*) FROM acc_transactions_lines AS b WHERE b.id_account = a.id) = 0
		AND user = 0
	);

UPDATE acc_accounts SET type = 0;

UPDATE acc_accounts SET type = 1 WHERE code LIKE '512_%' AND id_chart IN (SELECT id FROM acc_charts WHERE country = 'FR');
UPDATE acc_accounts SET type = 2 WHERE code LIKE '53_%' AND id_chart IN (SELECT id FROM acc_charts WHERE country = 'FR');
UPDATE acc_accounts SET type = 3 WHERE code LIKE '511_%' AND id_chart IN (SELECT id FROM acc_charts WHERE country = 'FR');
UPDATE acc_accounts SET type = 4 WHERE code LIKE '4_%' AND id_chart IN (SELECT id FROM acc_charts WHERE country = 'FR');
UPDATE acc_accounts SET type = 5 WHERE code LIKE '6_%' AND id_chart IN (SELECT id FROM acc_charts WHERE country = 'FR');
UPDATE acc_accounts SET type = 6 WHERE code LIKE '7_%' AND id_chart IN (SELECT id FROM acc_charts WHERE country = 'FR');
UPDATE acc_accounts SET type = 7 WHERE code LIKE '86_%' AND id_chart IN (SELECT id FROM acc_charts WHERE country = 'FR');
UPDATE acc_accounts SET type = 8 WHERE code LIKE '87_%' AND id_chart IN (SELECT id FROM acc_charts WHERE country = 'FR');
UPDATE acc_accounts SET type = 9 WHERE code = '890' AND id_chart IN (SELECT id FROM acc_charts WHERE country = 'FR');
UPDATE acc_accounts SET type = 10 WHERE code = '891' AND id_chart IN (SELECT id FROM acc_charts WHERE country = 'FR');
UPDATE acc_accounts SET type = 11 WHERE code = '120' AND id_chart IN (SELECT id FROM acc_charts WHERE country = 'FR');
UPDATE acc_accounts SET type = 12 WHERE code = '129' AND id_chart IN (SELECT id FROM acc_charts WHERE country = 'FR');
UPDATE acc_accounts SET type = 13 WHERE code = '1068' AND id_chart IN (SELECT id FROM acc_charts WHERE country = 'FR');
UPDATE acc_accounts SET type = 14 WHERE code = '110' AND id_chart IN (SELECT id FROM acc_charts WHERE country = 'FR');
UPDATE acc_accounts SET type = 15 WHERE code = '119' AND id_chart IN (SELECT id FROM acc_charts WHERE country = 'FR');



-- Cleanup
DROP TABLE acc_accounts_old;
DROP TABLE acc_transactions_lines_old;
DROP TABLE services_fees_old;