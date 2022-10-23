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

-- Force types
UPDATE acc_accounts SET position = 4 WHERE type = 5 AND position != 4;
UPDATE acc_accounts SET position = 5 WHERE type = 6 AND position != 5;
UPDATE acc_accounts SET position = 3 WHERE type IN (1, 2, 3, 4) AND position != 3;

-- Force analytical to be hidden
UPDATE acc_accounts SET position = 0 WHERE type = 7 AND position != 0;