-- Suppression de la colonne description des catégories
ALTER TABLE membres_categories RENAME TO membres_categories_old;

-- Re-créer la table
.read schema.sql

-- Copie des données, sauf la colonne description
INSERT INTO membres_categories SELECT id, nom, droit_wiki,
	droit_membres, droit_compta, droit_inscription,
	droit_connexion, droit_config, cacher,
	id_cotisation_obligatoire FROM membres_categories_old;

-- Suppression des anciennes tables
DROP TABLE membres_categories_old;
