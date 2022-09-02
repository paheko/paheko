CREATE TEMP TABLE IF NOT EXISTS su_fix_fee (id);

INSERT INTO su_fix_fee
	SELECT su.id FROM services_users su LEFT JOIN services_fees sf ON sf.id = su.id_fee AND sf.id_service = sf.id_service
	WHERE sf.id IS NULL AND su.id_fee IS NOT NULL;

-- Remove id_fee from subscriptions where it belongs to another service
UPDATE services_users SET id_fee = NULL WHERE id IN (SELECT id FROM su_fix_fee);