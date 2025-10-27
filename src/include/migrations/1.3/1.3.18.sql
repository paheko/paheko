-- Remove "(voir remarque X)" from account labels
UPDATE acc_accounts SET label = TRIM(REPLACE(REPLACE(REPLACE(label, '(voir remarque D)', ''), '(voir remarque C)', ''), '(voir remarque A)', ''))
	WHERE id_chart IN (SELECT id FROM acc_charts WHERE country = 'FR' AND code = 'PCS_2018');
