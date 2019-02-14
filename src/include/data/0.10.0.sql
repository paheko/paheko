ALTER TABLE compta_journal RENAME TO compta_journal_old;

.read schema.sql

INSERT INTO compta_journal_ecritures (id_journal, compte, debit, credit, montant)
	SELECT id, compte_credit, 0, CAST(montant * 100 AS INT) FROM compta_journal_old;

INSERT INTO compta_journal_ecritures (id_journal, compte, debit, credit, montant)
	SELECT id, compte_debit, CAST(montant * 100 AS INT), 0 FROM compta_journal_old;

INSERT INTO compta_journal (id, libelle, remarques, numero_piece, date, moyen_paiement, numero_cheque, id_exercice, id_auteur, id_categorie, id_projet)
	SELECT id, libelle, remarques, numero_piece, date, moyen_paiement, numero_cheque, id_exercice, id_auteur, id_categorie, id_projet FROM compta_journal_old;

DROP TABLE compta_journal_old;

-- CREATE TABLE IF NOT EXISTS compta_comptes_soldes
-- -- Soldes des comptes
-- (
--     compte TEXT NOT NULL REFERENCES compta_comptes(id) ON DELETE CASCADE,
--     exercice INTEGER NULL REFERENCES compta_exercices(id) ON DELETE CASCADE,
--     solde INTEGER NOT NULL,

--     PRIMARY KEY(compte, exercice)
-- );

-- CREATE TRIGGER IF NOT EXISTS ON compta_journal_ecritures

DROP TABLE compta_rapprochement;

-- Ajout moyens de paiement
INSERT OR IGNORE INTO compta_moyens_paiement (code, nom) VALUES ('AU', 'Autre');
INSERT OR IGNORE INTO compta_moyens_paiement (code, nom) VALUES ('AC', 'Autres chèques (vacances, cadeau, etc.)');

