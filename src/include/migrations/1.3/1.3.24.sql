DROP TRIGGER IF EXISTS acc_transactions_lines_update;

-- OLD.credit != NEW.credit comparison was missing
CREATE TRIGGER IF NOT EXISTS acc_transactions_lines_update AFTER UPDATE ON acc_transactions_lines
	WHEN OLD.id_letter IS NOT NULL AND (OLD.debit != NEW.debit
		OR OLD.credit != NEW.credit
		OR OLD.id_account != NEW.id_account
		OR OLD.id_letter != NEW.id_letter
		OR NEW.id_letter IS NULL)
	BEGIN
		DELETE FROM acc_letters WHERE id = OLD.id_letter;
	END;
