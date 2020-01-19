-- Il manquait une clause ON DELETE SET NULL sur la foreign key
-- de cotisations quand on faisait une mise à jour depuis une
-- ancienne version
ALTER TABLE cotisations RENAME TO cotisations_old;

.read 0.9.1_schema.sql

INSERT INTO cotisations SELECT * FROM cotisations_old;

DROP TABLE cotisations_old;

-- Changer le compte des reports automatiques
UPDATE compta_journal SET compte_debit = '890' WHERE compte_debit IS NULL;
UPDATE compta_journal SET compte_credit = '890' WHERE compte_credit IS NULL;
