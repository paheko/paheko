-- nouveau moyen de paiement
INSERT INTO compta_moyens_paiement (code, nom) VALUES ('AU', 'Autre');

CREATE TABLE transactions
-- Paiements possibles
(
    id INTEGER PRIMARY KEY,
    id_categorie_compta INTEGER NULL, -- NULL si le type n'est pas associé automatiquement à la compta

    intitule TEXT NOT NULL,
    description TEXT NULL,
    montant REAL NOT NULL,

    duree INTEGER NULL, -- En jours
    debut TEXT NULL, -- timestamp
    fin TEXT NULL,

    FOREIGN KEY (id_categorie_compta) REFERENCES compta_categories (id)
);

CREATE TABLE rappels
-- Rappels de devoir renouveller une transaction
(
    id INTEGER PRIMARY KEY,
    id_transaction INTEGER NULL,

    delai INTEGER NOT NULL, -- Délai en jours pour envoyer le rappel

    sujet TEXT NOT NULL,
    texte TEXT NOT NULL,

    FOREIGN KEY (id_transaction) REFERENCES transactions (id)
);

CREATE TABLE rappels_envoyes
-- Enregistrement des rappels envoyés à qui et quand
(
    id_membre INTEGER NOT NULL,
    id_rappel INTEGER NOT NULL,
    date TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    media INTEGER NOT NULL, -- Média utilisé pour le rappel : 1 = email, 2 = courrier, 3 = autre
    
    FOREIGN KEY (id_membre) REFERENCES membres (id),
    FOREIGN KEY (id_rappel) REFERENCES rappels (id),

    PRIMARY KEY(id_membre, id_rappel, date)
);

CREATE TABLE membres_transactions
-- Paiements enregistrés
(
    id INTEGER PRIMARY KEY,

    id_membre INTEGER NOT NULL,
    id_transaction INTEGER NULL, -- NULL si n'est pas relié à une transaction prévue

    libelle TEXT NULL,

    date TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    montant REAL NOT NULL,

    FOREIGN KEY (id_membre) REFERENCES membres (id),
    FOREIGN KEY (id_transaction) REFERENCES transactions (id)
);

CREATE TABLE membres_transactions_operations
-- Liaison paiements enregistrés avec écritures comptables
(
    id_operation INTEGER NOT NULL,
    id_membre_transaction INTEGER NOT NULL,

    FOREIGN KEY (id_operation) REFERENCES compta_journal (id),
    FOREIGN KEY (id_membre_transaction) REFERENCES membres_transactions (id)
);

-- Mise à jour des catégories

CREATE TABLE membres_categories_tmp
-- Catégories de membres
(
    id INTEGER PRIMARY KEY,
    nom TEXT,
    description TEXT,

    droit_wiki INT DEFAULT 1,
    droit_membres INT DEFAULT 1,
    droit_compta INT DEFAULT 1,
    droit_inscription INT DEFAULT 0,
    droit_connexion INT DEFAULT 1,
    droit_config INT DEFAULT 0,
    cacher INT DEFAULT 0,

    id_transaction_obligatoire INTEGER NULL,

    FOREIGN KEY (id_transaction_obligatoire) REFERENCES transactions (id)
);

-- Remise des anciennes infos
INSERT INTO membres_categories_tmp SELECT id, nom, description, droit_wiki, droit_membres, 
    droit_compta, droit_inscription, droit_connexion, droit_config, cacher, NULL FROM membres_categories;

-- Suppression de l'ancienne table et renommage de la nouvelle
DROP TABLE membres_categories;
ALTER TABLE membres_categories_tmp RENAME TO membres_categories;

-- Ajout id transaction aux écritures comptables
ALTER TABLE compta_journal ADD COLUMN id_transaction INTEGER NULL REFERENCES transactions (id);

-- Ajout désactivation compte
ALTER TABLE compta_comptes ADD COLUMN desactive INTEGER NOT NULL DEFAULT 0;

PRAGMA foreign_keys = ON;