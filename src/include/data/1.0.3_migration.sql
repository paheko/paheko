-- Fix services connected to old closed year
UPDATE services_fees SET id_year = NULL, id_account = NULL WHERE id_year IN (SELECT id FROM acc_years WHERE closed = 1);