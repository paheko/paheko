ALTER TABLE acc_charts RENAME TO acc_charts_old;

.read schema.sql

INSERT INTO acc_charts SELECT * FROM acc_charts_old;

-- Reset country if code was official, changing the country should not have been possible
UPDATE acc_charts SET country = 'FR' WHERE code IS NOT NULL AND code != 'PCMN_2019';
UPDATE acc_charts SET country = 'BE' WHERE code IS NOT NULL AND code = 'PCMN_2019';

-- Reset country to FR for countries using something similar
UPDATE acc_charts SET country = 'FR' WHERE country IN ('GN', 'TN', 'RE', 'CN', 'PF', 'MW', 'CI', 'GP', 'GA', 'DE', 'NC');

-- Set country to NULL if outside of supported countries
UPDATE acc_charts SET country = NULL WHERE country NOT IN ('FR', 'BE', 'CH');

-- Reset type to zero if not supported
UPDATE acc_accounts SET type = 0 WHERE id_chart IN (SELECT id FROM acc_charts WHERE country IS NULL);

-- Reset other charts in PHP code

DROP TABLE acc_charts_old;