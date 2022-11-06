ALTER TABLE acc_charts RENAME TO acc_charts_old;

.read schema.sql

INSERT INTO acc_charts SELECT * FROM acc_charts_old;
UPDATE acc_charts SET country = 'FR' WHERE code IN ('PCA_2018', 'PCA_1999');
UPDATE acc_charts SET country = NULL WHERE country NOT IN ('FR', 'BE');

UPDATE acc_accounts SET type = 0 WHERE id_chart IN (SELECT id FROM acc_charts WHERE country IS NULL);

DROP TABLE acc_charts_old;