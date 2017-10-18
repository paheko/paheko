-- Ajouter champ pour OTP
ALTER TABLE membres ADD COLUMN secret_otp TEXT NULL;

-- Ajouter champ clé PGP
ALTER TABLE membres ADD COLUMN clef_pgp TEXT NULL;

--------------------------------------------------------------------------------
-- Mise à jour des tables contenant un champ date pour ajouter la contrainte  --
-- Ceci afin de forcer les champs à contenir un format de date correct        --
-- On en profite pour ajouter les ON DELETE nécessaires                       --
--------------------------------------------------------------------------------

-- Convertir les dates UNIX en date Y-m-d, apparemment il y en a encore parfois ?
UPDATE wiki_pages SET date_creation = datetime(date_creation, "unixepoch") WHERE CAST(date_creation AS INT) = date_creation;
UPDATE wiki_pages SET date_creation = datetime(date_creation) WHERE datetime(date_creation) != date_creation;

-- Renommage des tables qu'il faut mettre à jour
ALTER TABLE cotisations_membres RENAME TO cotisations_membres_old;
ALTER TABLE rappels RENAME TO rappels_old;
ALTER TABLE rappels_envoyes RENAME TO rappels_envoyes_old;
ALTER TABLE wiki_pages RENAME TO wiki_pages_old;
ALTER TABLE wiki_revisions RENAME TO wiki_revisions_old;
ALTER TABLE compta_categories RENAME TO compta_categories_old;
ALTER TABLE compta_comptes_bancaires RENAME TO compta_comptes_bancaires_old;
ALTER TABLE compta_exercices RENAME TO compta_exercices_old;
ALTER TABLE compta_journal RENAME TO compta_journal_old;
ALTER TABLE compta_rapprochement RENAME TO compta_rapprochement_old;
ALTER TABLE fichiers RENAME TO fichiers_old;
ALTER TABLE membres_operations RENAME TO membres_operations_old;
ALTER TABLE membres_categories RENAME TO membres_categories_old;

-- Suppression des index pour que les nouveaux soient liés aux nouvelles tables
DROP INDEX cm_unique;
DROP INDEX wiki_uri;
DROP INDEX wiki_revisions_id_page;
DROP INDEX wiki_revisions_id_auteur;
DROP INDEX compta_operations_exercice;
DROP INDEX compta_operations_date;
DROP INDEX compta_operations_comptes;
DROP INDEX compta_operations_auteur;
DROP INDEX fichiers_date;

-- Suppression ancienne table recherche
DROP TABLE wiki_recherche;

-- Suppression des triggers
-- Sinon les nouveaux ne seront pas créés sur la nouvelle table
DROP TRIGGER wiki_recherche_delete;
DROP TRIGGER wiki_recherche_update;
DROP TRIGGER wiki_recherche_contenu_insert;
DROP TRIGGER wiki_recherche_contenu_chiffre;

-- Création des tables mises à jour (et de leurs index)
.read schema.sql

-- Copie des données
INSERT INTO cotisations_membres SELECT * FROM cotisations_membres_old;
INSERT INTO rappels SELECT * FROM rappels_old;
INSERT INTO rappels_envoyes SELECT id, id_membre, id_cotisation, id_rappel, date, media FROM rappels_envoyes_old;
INSERT INTO wiki_pages SELECT * FROM wiki_pages_old;
INSERT INTO wiki_revisions SELECT * FROM wiki_revisions_old;
INSERT INTO compta_categories SELECT * FROM compta_categories_old;
INSERT INTO compta_comptes_bancaires SELECT * FROM compta_comptes_bancaires_old;
INSERT INTO compta_exercices SELECT * FROM compta_exercices_old;
INSERT INTO compta_journal SELECT *, NULL FROM compta_journal_old;
INSERT INTO compta_rapprochement SELECT * FROM compta_rapprochement_old;
INSERT INTO fichiers SELECT * FROM fichiers_old;
INSERT INTO membres_operations SELECT * FROM membres_operations_old;
INSERT INTO membres_categories SELECT * FROM membres_categories_old;

-- Suppression des anciennes tables
DROP TABLE cotisations_membres_old;
DROP TABLE rappels_old;
DROP TABLE rappels_envoyes_old;
DROP TABLE wiki_pages_old;
DROP TABLE wiki_revisions_old;
DROP TABLE compta_categories_old;
DROP TABLE compta_comptes_bancaires_old;
DROP TABLE compta_exercices_old;
DROP TABLE compta_journal_old;
DROP TABLE compta_rapprochement_old;
DROP TABLE fichiers_old;
DROP TABLE membres_operations_old;
DROP TABLE membres_categories_old;
