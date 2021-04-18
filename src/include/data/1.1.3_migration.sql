-- Make sure id_account is reset when a year is deleted
CREATE TRIGGER IF NOT EXISTS acc_years_delete BEFORE DELETE ON acc_years BEGIN
    UPDATE services_fees SET id_account = NULL, id_year = NULL WHERE id_year = OLD.id;
END;
