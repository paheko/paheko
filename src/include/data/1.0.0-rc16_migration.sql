UPDATE acc_accounts SET position = 1 WHERE code = '486' AND id_chart IN (SELECT id FROM acc_charts WHERE code = 'PCA2018');
UPDATE acc_accounts SET position = 2 WHERE code = '487' AND id_chart IN (SELECT id FROM acc_charts WHERE code = 'PCA2018');
