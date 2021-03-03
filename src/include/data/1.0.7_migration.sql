-- Add indexes
DROP INDEX acc_transactions_type;
CREATE INDEX IF NOT EXISTS acc_transactions_type ON acc_transactions (type, id_year);

CREATE INDEX IF NOT EXISTS acc_transactions_lines_transaction ON acc_transactions_lines (id_transaction);
