ALTER TABLE compta_journal RENAME TO compta_journal_old;
ALTER TABLE compta_comptes RENAME TO compta_comptes_old;
ALTER TABLE compta_categories RENAME TO compta_categories_old;
ALTER TABLE compta_exercices RENAME TO compta_exercices_old;
ALTER TABLE membres_operations RENAME TO membres_operations_old;
ALTER TABLE membres_categories RENAME TO membres_categories_old;

DROP TABLE fichiers_compta_journal; -- Inutilisé à ce jour

-- N'est pas utilisé
DELETE FROM config WHERE cle = 'categorie_dons' OR cle = 'categorie_cotisations';

.read 1.0.0_schema.sql

-- FIXME: insertion en comptes analytiques des projets et associations dans transactions

INSERT INTO acc_plans (id, country, code, label) VALUES (1, 'FR', 'PCG1999', 'Plan comptable associatif (France, 1999-2019)');

--.read plan_comptable_1999.sql
--.read plan_comptable_2020.sql

-- Migration comptes de code comme identifiant à ID unique
INSERT INTO acc_accounts (id, id_plan, code, parent, label, position, user, id_exercice)
	SELECT NULL, 1, id, NULL, libelle, position, plan_comptable, NULL FROM compta_comptes_old;

-- Migration de la hiérarchie
UPDATE acc_accounts AS a SET parent = (SELECT id FROM acc_accounts AS b WHERE code = (SELECT parent FROM compta_comptes_old AS c WHERE id = b.code));

-- Migrations projets vers comptes analytiques
INSERT INTO acc_accounts (id_plan, code, parent, label, position, user, type)
	VALUES (1, '99', (SELECT id FROM acc_accounts WHERE code = '9'), 'Projets', 0, 0, 4);

INSERT INTO acc_accounts (id_plan, code, parent, label, position, user, type)
	SELECT 1, '99' || substr('0000' || id, -4), (SELECT id FROM acc_accounts WHERE code = '99'), libelle, 0, 1, 3 FROM compta_projets;

-- Migration comptes bancaires
UPDATE acc_accounts SET type = 1 WHERE code IN (SELECT id FROM compta_comptes_bancaires);

-- Caisse
UPDATE acc_accounts SET type = 2 WHERE code = '530';

-- Chèques et carte à encaisser
UPDATE acc_accounts SET type = 3 WHERE code = '5112' OR code = '5113';

-- Bénévolat en nature
UPDATE acc_accounts SET type = 5 WHERE code = '870';

-- Recopie des mouvements
INSERT INTO acc_transactions (id, label, notes, reference, date, id_year, id_category, id_analytical)
	SELECT id, libelle, remarques, numero_piece, date, id_exercice, id_categorie,
	CASE WHEN id_projet IS NOT NULL THEN (SELECT id FROM acc_accounts WHERE code = '99' || substr('0000' || id_projet, -4)) ELSE NULL END
	FROM compta_journal_old;

-- Création des lignes associées aux mouvements
INSERT INTO acc_transactions_lines (id_transaction, id_account, debit, credit, payment_reference)
	SELECT id, (SELECT id FROM acc_accounts WHERE code = compte_credit), 0, CAST(montant * 100 AS INT), numero_cheque FROM compta_journal_old;

INSERT INTO acc_transactions_lines (id_transaction, id_account, debit, credit)
	SELECT id, (SELECT id FROM acc_accounts WHERE code = compte_debit), CAST(montant * 100 AS INT), 0, numero_cheque FROM compta_journal_old;

-- Recopie des descriptions de catégories dans la table des comptes
UPDATE acc_accounts a SET (a.type, a.description) = (SELECT CASE WHEN c.type = -1 THEN 6 ELSE 7 END, c.description FROM compta_categories_old c WHERE c.id = a.code)
	WHERE EXISTS (SELECT rowid FROM compta_categories_old c WHERE c.id = a.code);

-- Recopie des opérations, mais le nom a changé pour "mouvements"
INSERT INTO membres_mouvements
	SELECT * FROM membres_operations_old;

-- FIXME: ajout d'entrées dans le le log utilisateur à partir de id_auteur

-- Recopie des exercices, mais la date de fin ne peut être nulle
INSERT INTO acc_years (id, label, start_date, end_date, closed, id_plan)
	SELECT id, libelle, debut, CASE WHEN fin IS NULL THEN date(debut, '+1 year') ELSE fin END, cloture, 1 FROM compta_exercices_old;

-- Recopie des catégories, on supprime la colonne id_cotisation_obligatoire
INSERT INTO membres_categories
	SELECT id, nom, droit_wiki, droit_membres, droit_compta, droit_inscription, droit_connexion, droit_config, cacher FROM membres_categories_old;

DROP TABLE compta_journal_old;
DROP TABLE membres_operations_old;
DROP TABLE compta_categories_old;
DROP TABLE compta_comptes_old;
DROP TABLE compta_exercices_old;

-- Transfert des rapprochements
UPDATE acc_transactions_lines SET reconcilied = 1 WHERE id_transaction IN (SELECT id_operation FROM compta_rapprochement);

-- Suppression de la table rapprochements
DROP TABLE compta_rapprochement;

-- Suppression inutilisées
DROP TABLE compta_projets;
DROP TABLE compta_comptes_bancaires;
DROP TABLE compta_moyens_paiements;


