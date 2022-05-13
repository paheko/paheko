ALTER TABLE services_fees ADD COLUMN id_analytical INTEGER NULL REFERENCES acc_accounts (id) ON DELETE SET NULL;

UPDATE acc_charts SET code = 'PCA_2018' WHERE code = 'PCA2018';
UPDATE acc_charts SET code = 'PCA_1999' WHERE code = 'PCA1999';
