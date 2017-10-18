DROP TABLE compta_exercices;

CREATE TABLE compta_exercices
-- Exercices
(
	id INTEGER PRIMARY KEY,

	libelle TEXT NOT NULL,

	debut TEXT NOT NULL DEFAULT CURRENT_DATE,
	fin TEXT NULL DEFAULT NULL,

	cloture INTEGER NOT NULL DEFAULT 0
);

INSERT INTO compta_exercices (libelle, debut, fin, cloture)
	VALUES (
		'Premier exercice',
		(CASE WHEN
			(SELECT strftime('%Y-01-01', date) FROM compta_journal ORDER BY date ASC LIMIT 1)
			IS NOT NULL THEN (SELECT strftime('%Y-01-01', date) FROM compta_journal ORDER BY date ASC LIMIT 1)
			ELSE strftime('%Y-01-01', 'now') END
		),
		(CASE WHEN
			(SELECT strftime('%Y-12-31', date) FROM compta_journal ORDER BY date DESC LIMIT 1)
			IS NOT NULL THEN (SELECT strftime('%Y-12-31', date) FROM compta_journal ORDER BY date DESC LIMIT 1)
			ELSE strftime('%Y-12-31', 'now') END
		),
		0
	);

BEGIN;
ALTER TABLE compta_journal RENAME TO old_compta_journal;
DROP INDEX compta_operations_exercice;
DROP INDEX compta_operations_date;
DROP INDEX compta_operations_comptes;
DROP INDEX compta_operations_auteur;

CREATE TABLE compta_journal
-- Journal des opérations comptables
(
	id INTEGER PRIMARY KEY,

	libelle TEXT NOT NULL,
	remarques TEXT,
	numero_piece TEXT, -- N° de pièce comptable

	montant REAL,

	date TEXT DEFAULT CURRENT_DATE,
	moyen_paiement TEXT DEFAULT NULL,
	numero_cheque TEXT DEFAULT NULL,

	compte_debit TEXT, -- N° du compte dans le plan
	compte_credit TEXT, -- N° du compte dans le plan

	id_exercice INTEGER NULL DEFAULT NULL, -- En cas de compta simple, l'exercice est permanent (NULL)
	id_auteur INTEGER NULL,
	id_categorie INTEGER NULL, -- Numéro de catégorie (en mode simple)

	FOREIGN KEY(moyen_paiement) REFERENCES compta_moyens_paiement(code),
	FOREIGN KEY(compte_debit) REFERENCES compta_comptes(id),
	FOREIGN KEY(compte_credit) REFERENCES compta_comptes(id),
	FOREIGN KEY(id_exercice) REFERENCES compta_exercices(id),
	FOREIGN KEY(id_auteur) REFERENCES membres(id),
	FOREIGN KEY(id_categorie) REFERENCES compta_categories(id)
);

CREATE INDEX compta_operations_exercice ON compta_journal (id_exercice);
CREATE INDEX compta_operations_date ON compta_journal (date);
CREATE INDEX compta_operations_comptes ON compta_journal (compte_debit, compte_credit);
CREATE INDEX compta_operations_auteur ON compta_journal (id_auteur);

INSERT INTO compta_journal SELECT * FROM old_compta_journal;

UPDATE compta_journal SET id_exercice = 1;

DROP TABLE old_compta_journal;
END;