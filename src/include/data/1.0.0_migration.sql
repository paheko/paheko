ALTER TABLE membres_operations RENAME TO membres_operations_old;
ALTER TABLE membres_categories RENAME TO membres_categories_old;

DROP TABLE fichiers_compta_journal; -- Inutilisé à ce jour

-- Fix: comptes de clôture et fermeture
UPDATE compta_comptes SET libelle = 'Bilan d''ouverture' WHERE id = '890' AND libelle = 'Bilan de clôture';
INSERT OR REPLACE INTO compta_comptes (id, parent, libelle, position) VALUES ('891', '89', 'Bilan de clôture', 0);

-- N'est pas utilisé
DELETE FROM config WHERE cle = 'categorie_dons' OR cle = 'categorie_cotisations';

.read 1.0.0_schema.sql

-- FIXME: insertion en comptes analytiques des projets et associations dans transactions

-------- MIGRATION COMPTA ---------
INSERT INTO acc_charts (id, country, code, label) VALUES (1, 'FR', 'PCGA1999', 'Plan comptable associatif 1999');

-- Migration comptes de code comme identifiant à ID unique
-- Inversement valeurs actif/passif
INSERT INTO acc_accounts (id, id_chart, code, label, position, user)
	SELECT NULL, 1, id, libelle, CASE WHEN position = 2 THEN 1 WHEN position = 1 THEN 2 ELSE position END, CASE WHEN plan_comptable = 1 THEN 0 ELSE 1 END FROM compta_comptes;

-- Migrations projets vers comptes analytiques
INSERT INTO acc_accounts (id_chart, code, label, position, user, type)
	VALUES (1, '99', 'Projets', 0, 1, 6);

INSERT INTO acc_accounts (id_chart, code, label, position, user, type)
	SELECT 1, '99' || substr('0000' || id, -4), libelle, 0, 1, 6 FROM compta_projets;

-- Suppression de la position "charge ou produit" qui n'a aucun sens
UPDATE acc_accounts SET position = 0 WHERE position = 12;

-- Modification des valeurs de la position (qui n'est plus un champ binaire)
UPDATE acc_accounts SET position = 5 WHERE position = 8;

-- Mise à jour de la position pour les comptes de tiers qui peuvent varier actif ou passif
UPDATE acc_accounts SET position = 3 WHERE code IN (4010, 4110, 4210, 428, 438);

-- Mise à jour position comptes bancaires, qui peuvent être à découvert et donc changer de côté au bilan
UPDATE acc_accounts SET position = 3 WHERE code LIKE '512%';

-- Migration comptes bancaires
UPDATE acc_accounts SET type = 3 WHERE code IN (SELECT id FROM compta_comptes_bancaires);

-- Caisse
UPDATE acc_accounts SET type = 4 WHERE code = '530';

-- Chèques et carte à encaisser
UPDATE acc_accounts SET type = 5 WHERE code = '5112' OR code = '5113';

-- Comptes d'ouverture et de clôture
UPDATE acc_accounts SET type = 9 WHERE code = '890';
UPDATE acc_accounts SET type = 10 WHERE code = '891';

-- Comptes de tiers
UPDATE acc_accounts SET type = 8 WHERE code IN (SELECT id FROM compta_comptes WHERE id LIKE '4%' AND plan_comptable = 0 AND desactive = 0);

-- Recopie des mouvements
INSERT INTO acc_transactions (id, label, notes, reference, date, id_year, id_creator)
	SELECT id, libelle, remarques, numero_piece, date, id_exercice, id_auteur
	FROM compta_journal;

-- Création des lignes associées aux mouvements
INSERT INTO acc_transactions_lines (id_transaction, id_account, debit, credit, reference, id_analytical)
	SELECT id, (SELECT id FROM acc_accounts WHERE code = compte_credit), 0, CAST(REPLACE(montant * 100, '.0', '') AS INT), numero_cheque,
	CASE WHEN id_projet IS NOT NULL THEN (SELECT id FROM acc_accounts WHERE code = '99' || substr('0000' || id_projet, -4)) ELSE NULL END
	FROM compta_journal;

INSERT INTO acc_transactions_lines (id_transaction, id_account, debit, credit, reference, id_analytical)
	SELECT id, (SELECT id FROM acc_accounts WHERE code = compte_debit), CAST(REPLACE(montant * 100, '.0', '') AS INT), 0, numero_cheque,
	CASE WHEN id_projet IS NOT NULL THEN (SELECT id FROM acc_accounts WHERE code = '99' || substr('0000' || id_projet, -4)) ELSE NULL END
	FROM compta_journal;

-- Recopie des descriptions de catégories dans la table des comptes, et mise des comptes en signets
UPDATE acc_accounts SET type = 1, description = (SELECT description FROM compta_categories WHERE compte = acc_accounts.code)
	WHERE id IN (SELECT a.id FROM acc_accounts a INNER JOIN compta_categories c ON c.compte = a.code AND c.type = 1);

UPDATE acc_accounts SET type = 2, description = (SELECT description FROM compta_categories WHERE compte = acc_accounts.code)
	WHERE id IN (SELECT a.id FROM acc_accounts a INNER JOIN compta_categories c ON c.compte = a.code AND c.type = -1 AND c.compte NOT LIKE '4%');

UPDATE acc_accounts SET type = 8, description = (SELECT description FROM compta_categories WHERE compte = acc_accounts.code)
	WHERE id IN (SELECT a.id FROM acc_accounts a INNER JOIN compta_categories c ON c.compte = a.code AND c.type = -1 AND c.compte LIKE '4%');

-- Recopie des opérations, mais le nom a changé pour acc_transactions_users
INSERT INTO acc_transactions_users
	SELECT * FROM membres_operations_old;

-- FIXME: ajout d'entrées dans le le log utilisateur à partir de id_auteur

-- Recopie des exercices, mais la date de fin ne peut être nulle
INSERT INTO acc_years (id, label, start_date, end_date, closed, id_chart)
	SELECT id, libelle, debut, CASE WHEN fin IS NULL THEN date(debut, '+1 year') ELSE fin END, cloture, 1 FROM compta_exercices;

-- Recopie des catégories, on supprime la colonne id_cotisation_obligatoire
INSERT INTO membres_categories
	SELECT id, nom, droit_wiki, droit_membres, droit_compta, droit_inscription, droit_connexion, droit_config, cacher FROM membres_categories_old;

-- Transfert des rapprochements
UPDATE acc_transactions_lines SET reconciled = 1 WHERE id_transaction IN (SELECT id_operation FROM compta_rapprochement);

--------- MIGRATION COTISATIONS ----------

INSERT INTO services SELECT id, intitule, description, duree, debut, fin FROM cotisations;
INSERT INTO services_users SELECT cm.id, cm.id_membre, cm.id_cotisation,
	NULL,
	1,
	CASE
		WHEN c.duree IS NOT NULL THEN date(cm.date, '+'||c.duree||' days')
		WHEN c.fin IS NOT NULL THEN c.fin
		ELSE NULL
	END
	FROM cotisations_membres cm
	INNER JOIN cotisations c ON c.id = cm.id_cotisation;

INSERT INTO services_fees (label, amount, id_service, id_account)
	SELECT intitule, CAST(montant AS integer), id,
		(SELECT id FROM acc_accounts WHERE code = (SELECT compte FROM compta_categories WHERE id = id_categorie_compta))
	FROM cotisations WHERE montant > 0 OR id_categorie_compta IS NOT NULL;

INSERT INTO services_reminders SELECT * FROM rappels;
INSERT INTO services_reminders_sent SELECT id, id_membre, id_cotisation, id_rappel, date FROM rappels_envoyes;

DROP TABLE cotisations;
DROP TABLE cotisations_membres;
DROP TABLE rappels;
DROP TABLE rappels_envoyes;

-- Suppression inutilisées
DROP TABLE compta_rapprochement;
DROP TABLE compta_journal;
DROP TABLE compta_categories;
DROP TABLE compta_comptes;
DROP TABLE compta_exercices;
DROP TABLE membres_operations_old;

DROP TABLE compta_projets;
DROP TABLE compta_comptes_bancaires;
DROP TABLE compta_moyens_paiement;
