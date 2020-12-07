UPDATE acc_accounts SET type = 8, position = 4 WHERE id_chart = (SELECT id FROM acc_charts WHERE code IS NOT NULL) AND (code LIKE '86_%');
UPDATE acc_accounts SET type = 8, position = 5 WHERE id_chart = (SELECT id FROM acc_charts WHERE code IS NOT NULL) AND (code LIKE '87_%');
