-- Ajout d'une clause ON DELETE SET NULL sur la table cotisations
ALTER TABLE cotisations_membres RENAME TO cotisations_membres_old;

-- Création des tables mises à jour (et de leurs index)
.read schema.sql

-- Copie des données
INSERT INTO cotisations_membres SELECT * FROM cotisations_membres_old;

-- Suppression des anciennes tables
DROP TABLE cotisations_membres_old;
