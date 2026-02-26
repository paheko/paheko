-- Add new column to services
ALTER TABLE services ADD COLUMN archived INTEGER NOT NULL DEFAULT 0;

UPDATE services SET archived = CASE WHEN end_date IS NOT NULL AND end_date < datetime() THEN 1 ELSE 0 END;

CREATE INDEX IF NOT EXISTS services_archived ON services (archived);

-- Create new config key
INSERT INTO config (key, value) VALUES ('show_category_in_list', 1);

-- Add status to transactions lines
ALTER TABLE acc_transactions_lines ADD COLUMN status INTEGER NOT NULL DEFAULT 0;
CREATE INDEX IF NOT EXISTS acc_transactions_lines_status ON acc_transactions_lines (status);

UPDATE acc_transactions_lines SET status = 4 WHERE id_transaction IN (SELECT id FROM acc_transactions WHERE status & 4);

-- Remove deposited status from acc_transactions table
UPDATE acc_transactions SET status = (status & ~4);

-- Remove "(voir remarque X)" from account labels
UPDATE acc_accounts SET label = TRIM(REPLACE(REPLACE(REPLACE(label, '(voir remarque D)', ''), '(voir remarque C)', ''), '(voir remarque A)', ''))
	WHERE id_chart IN (SELECT id FROM acc_charts WHERE country = 'FR' AND code = 'PCS_2018');

