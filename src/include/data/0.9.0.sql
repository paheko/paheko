-- Désactivation de l'accès aux membres, pour les groupes qui n'avaient que le droit de lecture
-- car maintenant ce droit permet de voir les fiches de membres complètes
UPDATE membres_categories SET droit_membres = 0 WHERE droit_membres = 1;

-- Suppression de la colonne description des catégories
ALTER TABLE membres_categories RENAME TO membres_categories_old;

-- Mise à jour table compta_rapprochement: la foreign key sur membres est passée
-- à ON DELETE SET NULL
ALTER TABLE compta_rapprochement RENAME TO compta_rapprochement_old;

-- Re-créer la table
.read schema.sql

-- Copie des données, sauf la colonne description
INSERT INTO membres_categories SELECT id, nom, droit_wiki,
	droit_membres, droit_compta, droit_inscription,
	droit_connexion, droit_config, cacher,
	id_cotisation_obligatoire FROM membres_categories_old;

-- Suppression des anciennes tables
DROP TABLE membres_categories_old;

-- Migration des données
INSERT INTO compta_rapprochement SELECT * FROM compta_rapprochement_old;
DROP TABLE compta_rapprochement_old;