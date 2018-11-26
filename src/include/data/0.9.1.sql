-- Il manquait une clause ON DELETE SET NULL sur la foreign key
-- de cotisations quand on faisait une mise à jour depuis une
-- ancienne version
ALTER TABLE cotisations RENAME TO cotisations_old;

.read schema.sql

INSERT INTO cotisations SELECT * FROM cotisations_old;

DROP TABLE cotisations_old;