UPDATE acc_accounts SET type = 8, position = 4 WHERE id_chart = (SELECT id FROM acc_charts WHERE code IS NOT NULL) AND (code LIKE '86_%');
UPDATE acc_accounts SET type = 8, position = 5 WHERE id_chart = (SELECT id FROM acc_charts WHERE code IS NOT NULL) AND (code LIKE '87_%');

UPDATE acc_accounts SET position = 3 WHERE code IN ('5112', '5115', '530') AND code IS NOT NULL;