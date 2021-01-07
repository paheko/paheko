UPDATE acc_accounts SET position = 3 WHERE code = '445' OR code = '444' AND id_chart IN (SELECT id FROM acc_charts WHERE code = 'PCGA1999');

UPDATE acc_transactions SET label = '[ERREUR ! À corriger !] ' || label, status = 8 WHERE id IN (
	SELECT DISTINCT t.id
		FROM acc_transactions t
		INNER JOIN acc_transactions_lines l ON l.id_transaction = t.id
		INNER JOIN acc_accounts a ON l.id_account = a.id
		INNER JOIN acc_years y ON y.id = t.id_year AND y.closed = 0 AND y.id_chart != a.id_chart
);

UPDATE services_fees SET id_account = NULL, id_year = NULL WHERE id_account IN (
	SELECT sf.id_account FROM services_fees sf
		INNER JOIN acc_accounts a ON a.id = sf.id_account
		INNER JOIN acc_years y ON y.id = sf.id_year
	WHERE a.id_chart != y.id_chart);