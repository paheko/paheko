-- Fix credit/debt payment types
UPDATE acc_transactions SET type = 0 WHERE id_related IS NOT NULL AND type IN (4,5);