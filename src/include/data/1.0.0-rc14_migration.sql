-- Put 890, 891 accounts in balance sheet, though it's not really correct...
UPDATE acc_accounts SET position = 3 WHERE
	code IN (890, 891)
	AND id_chart IN (SELECT id FROM acc_charts WHERE code IS NOT NULL);
