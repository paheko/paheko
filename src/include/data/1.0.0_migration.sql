ALTER TABLE compta_journal RENAME TO compta_journal_old;
ALTER TABLE compta_comptes RENAME TO compta_comptes_old;
ALTER TABLE compta_categories RENAME TO compta_categories_old;
ALTER TABLE compta_exercices RENAME TO compta_exercices_old;
ALTER TABLE membres_operations RENAME TO membres_operations_old;

DROP TABLE fichiers_compta_journal; -- Inutilisé à ce jour

.read 1.0.0_schema.sql

-- Migration comptes de code comme identifiant à ID unique
INSERT INTO compta_comptes (id, code, parent, libelle, position, plan_comptable, id_exercice)
	SELECT NULL, id, NULL, libelle, position, plan_comptable, NULL FROM compta_comptes_old;

-- Migration de la hiérarchie
UPDATE compta_comptes AS a SET parent = (SELECT id FROM compta_comptes AS b WHERE code = (SELECT parent FROM compta_comptes_old AS c WHERE id = b.code));

-- Création archives comptes des exercices précédents faite dans upgrade.php !

-- Recopie des mouvements
INSERT INTO compta_mouvements (id, libelle, remarques, numero_piece, date, moyen_paiement, reference_paiement, id_exercice, id_auteur, id_categorie, id_projet)
	SELECT id, libelle, remarques, numero_piece, date, moyen_paiement, numero_cheque, id_exercice, id_auteur, id_categorie, id_projet FROM compta_journal_old;

-- Création des lignes associées aux mouvements
INSERT INTO compta_mouvements_lignes (id_mouvement, compte, debit, credit)
	SELECT id, (SELECT id FROM compta_comptes WHERE code = compte_credit), 0, CAST(montant * 100 AS INT) FROM compta_journal_old;

INSERT INTO compta_mouvements_lignes (id_mouvement, compte, debit, credit)
	SELECT id, (SELECT id FROM compta_comptes WHERE code = compte_debit), CAST(montant * 100 AS INT), 0 FROM compta_journal_old;

-- Recopie des catégories avec les nouveaux ID de comptes
INSERT INTO compta_categories
	SELECT id, type, intitule, description, (SELECT id FROM compta_comptes WHERE code = compte) FROM compta_categories_old;

-- Recopie des opérations, mais le nom a changé pour "mouvements"
INSERT INTO membres_mouvements
	SELECT * FROM membres_operations_old;

-- Recopie des exercices, mais la date de fin ne peut être nulle
INSERT INTO compta_exercices
	SELECT id, libelle, debut, CASE WHEN fin IS NULL THEN date(debut, '+1 year') ELSE fin END, cloture FROM compta_exercices_old;

DROP TABLE compta_journal_old;
DROP TABLE membres_operations_old;
DROP TABLE compta_categories_old;
DROP TABLE compta_comptes_old;
DROP TABLE compta_exercices_old;

-- CREATE TABLE IF NOT EXISTS compta_comptes_soldes
-- -- Soldes des comptes
-- (
--     compte TEXT NOT NULL REFERENCES compta_comptes(id) ON DELETE CASCADE,
--     exercice INTEGER NULL REFERENCES compta_exercices(id) ON DELETE CASCADE,
--     solde INTEGER NOT NULL,

--     PRIMARY KEY(compte, exercice)
-- );

-- CREATE TRIGGER IF NOT EXISTS ON compta_journal_ecritures

-- Transfert des rapprochements
UPDATE compta_mouvements_lignes SET rapprochement = 1 WHERE id_mouvement IN (SELECT id_operation FROM compta_rapprochement);

-- Suppression de la table rapprochements
DROP TABLE compta_rapprochement;

