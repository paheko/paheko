CREATE TEMP TABLE tmp_new_accounts (id_chart, code, label, position);

-- Add missing accounts
INSERT INTO tmp_new_accounts (code, label, position) VALUES
	('438', 'Organismes sociaux - Charges à payer et produits à recevoir', 2),
	('4382', 'Charges sociales sur congés à payer', 2),
	('4386', 'Autres charges à payer', 2),
	('4387', 'Produits à recevoir', 2);

UPDATE tmp_new_accounts SET id_chart = (SELECT id FROM acc_charts WHERE code = 'PCA_2018');

INSERT OR IGNORE INTO acc_accounts (id_chart, code, label, position, user) SELECT *, 0 FROM tmp_new_accounts WHERE id_chart IS NOT NULL;

DROP TABLE tmp_new_accounts;

CREATE TEMP TABLE IF NOT EXISTS su_fix_fee (id);

INSERT INTO su_fix_fee
	SELECT su.id FROM services_users su LEFT JOIN services_fees sf ON sf.id = su.id_fee AND sf.id_service = sf.id_service
	WHERE sf.id IS NULL AND su.id_fee IS NOT NULL;

-- Remove id_fee from subscriptions where it belongs to another service
UPDATE services_users SET id_fee = NULL WHERE id IN (SELECT id FROM su_fix_fee);