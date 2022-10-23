-- New config value
INSERT INTO config (key, value) VALUES ('analytical_set_all', 1);

-- Fix charts positions

-- Force all third party accounts to be in position 3 (active or passive)
UPDATE acc_accounts SET position = 3
	WHERE id_chart IN (SELECT id FROM acc_charts WHERE country = 'FR')
	AND position NOT IN (0, 1, 2, 3)
	AND SUBSTR(code, 1, 1) IN ('1', '2', '3', '4', '5');

-- Force all expense accounts to be in expense position
UPDATE acc_accounts SET position = 4
	WHERE id_chart IN (SELECT id FROM acc_charts WHERE country = 'FR')
	AND position NOT IN (0, 4)
	AND code LIKE '6%';

-- Force all revenue accounts to be in revenue position
UPDATE acc_accounts SET position = 5
	WHERE id_chart IN (SELECT id FROM acc_charts WHERE country = 'FR')
	AND position NOT IN (0, 5)
	AND code LIKE '7%';

-- Force all volunteering accounts
UPDATE acc_accounts SET position = 4
	WHERE id_chart IN (SELECT id FROM acc_charts WHERE country = 'FR')
	AND position NOT IN (0, 4)
	AND code LIKE '86_%';

UPDATE acc_accounts SET position = 5
	WHERE id_chart IN (SELECT id FROM acc_charts WHERE country = 'FR')
	AND position NOT IN (0, 5)
	AND code LIKE '87_%';

-- Force analytical to be hidden
UPDATE acc_accounts SET position = 0 WHERE type = 7 AND position != 0;

-- Remove type from accounts that are outside their class
UPDATE acc_accounts SET type = 0 WHERE type = 1 AND code NOT LIKE '51%';
UPDATE acc_accounts SET type = 0 WHERE type = 2 AND code NOT LIKE '53%';
UPDATE acc_accounts SET type = 0 WHERE type = 3 AND code NOT LIKE '511%';
UPDATE acc_accounts SET type = 0 WHERE type = 4 AND code NOT LIKE '4%';
UPDATE acc_accounts SET type = 0 WHERE type = 5 AND code NOT LIKE '6%';
UPDATE acc_accounts SET type = 0 WHERE type = 6 AND code NOT LIKE '7%';
UPDATE acc_accounts SET type = 0 WHERE type = 7 AND code NOT LIKE '9%';
UPDATE acc_accounts SET type = 0 WHERE type = 8 AND code NOT LIKE '8%';