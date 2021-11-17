UPDATE acc_accounts SET type = 8, position = 4 WHERE id_chart IN (SELECT id FROM acc_charts WHERE code IS NOT NULL) AND (code LIKE '86_%') AND user = 0;
UPDATE acc_accounts SET type = 8, position = 5 WHERE id_chart IN (SELECT id FROM acc_charts WHERE code IS NOT NULL) AND (code LIKE '87_%') AND user = 0;
