CREATE TABLE compta_exercices
-- Exercices
(
    id INTEGER PRIMARY KEY,

    libelle TEXT NOT NULL,

    debut TEXT NOT NULL DEFAULT CURRENT_DATE,
    fin TEXT NULL DEFAULT NULL,

    clos INTEGER NOT NULL DEFAULT 0
);


CREATE TABLE compta_comptes
-- Plan comptable
(
    id TEXT PRIMARY KEY,
    parent TEXT NOT NULL DEFAULT 0,

    libelle TEXT NOT NULL,

    position INTEGER NOT NULL, -- position actif/passif/charge/produit
    plan_comptable INTEGER NOT NULL DEFAULT 1 -- 1 = fait partie du plan comptable, 0 = a été ajouté par l'utilisateur
);

CREATE INDEX compta_comptes_parent ON compta_comptes (parent);

CREATE TABLE compta_comptes_bancaires
-- Comptes bancaires
(
    id TEXT PRIMARY KEY,

    banque TEXT NOT NULL,

    iban TEXT,
    bic TEXT,

    FOREIGN KEY(id) REFERENCES compta_comptes(id)
);

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

    compte_debit INTEGER, -- N° du compte dans le plan
    compte_credit INTEGER, -- N° du compte dans le plan

    id_exercice INTEGER NULL DEFAULT NULL, -- En cas de compta simple, l'exercice est permanent (NULL)
    id_auteur INTEGER NOT NULL,
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

CREATE TABLE compta_moyens_paiement
-- Moyens de paiement
(
    code TEXT PRIMARY KEY,
    nom TEXT
);

INSERT INTO compta_moyens_paiement (code, nom) VALUES ('CB', 'Carte bleue');
INSERT INTO compta_moyens_paiement (code, nom) VALUES ('CH', 'Chèque');
INSERT INTO compta_moyens_paiement (code, nom) VALUES ('ES', 'Espèces');
INSERT INTO compta_moyens_paiement (code, nom) VALUES ('PR', 'Prélèvement');
INSERT INTO compta_moyens_paiement (code, nom) VALUES ('TI', 'TIP');
INSERT INTO compta_moyens_paiement (code, nom) VALUES ('VI', 'Virement');

CREATE TABLE compta_categories
-- Catégories pour simplifier le plan comptable
(
    id INTEGER PRIMARY KEY,
    type INTEGER DEFAULT 1, -- 1 = recette, -1 = dépense, 0 = autre (utilisé uniquement pour l'interface)

    intitule TEXT NOT NULL,
    description TEXT,

    compte TEXT NOT NULL, -- Compte affecté par cette catégorie

    FOREIGN KEY(compte) REFERENCES compta_comptes(id)
);