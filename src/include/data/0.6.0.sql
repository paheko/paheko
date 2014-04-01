CREATE TABLE cotisations
-- Types de cotisations et activités
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

CREATE TABLE cotisations_membres
-- Enregistrement des cotisations et activités
(
    id INTEGER NOT NULL PRIMARY KEY,
    id_membre INTEGER NOT NULL REFERENCES membres (id),
    id_cotisation INTEGER NOT NULL REFERENCES cotisations (id),

    date TEXT NOT NULL DEFAULT CURRENT_DATE
);

CREATE UNIQUE INDEX cm_unique ON cotisations_membres (id_membre, id_cotisation, date);

CREATE TABLE membres_operations
-- Liaision des enregistrement des paiements en compta
(
    id_membre INTEGER NOT NULL REFERENCES membres (id),
    id_operation INTEGER NOT NULL REFERENCES compta_journal (id),
    id_cotisation INTEGER NULL REFERENCES cotisations_membres (id),

    PRIMARY KEY (id_membre, id_operation)
);

CREATE TABLE rappels
-- Rappels de devoir renouveller une cotisation
(
    id INTEGER PRIMARY KEY,
    id_cotisation INTEGER NOT NULL REFERENCES cotisations (id),

    delai INTEGER NOT NULL, -- Délai en jours pour envoyer le rappel

    sujet TEXT NOT NULL,
    texte TEXT NOT NULL
);

CREATE TABLE rappels_envoyes
-- Enregistrement des rappels envoyés à qui et quand
(
    id INTEGER PRIMARY KEY,

    id_membre INTEGER NOT NULL REFERENCES membres (id),
    id_cotisation INTEGER NOT NULL REFERENCES cotisations (id),

    date TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,

    media INTEGER NOT NULL -- Média utilisé pour le rappel : 1 = email, 2 = courrier, 3 = autre
);

CREATE TABLE plugins
-- Plugins / extensions
(
    id TEXT PRIMARY KEY,
    officiel INTEGER NOT NULL DEFAULT 0,
    nom TEXT NOT NULL,
    description TEXT,
    auteur TEXT,
    url TEXT,
    version TEXT NOT NULL,
    menu INTEGER NOT NULL DEFAULT 0,
    config TEXT
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

    id_cotisation_obligatoire INTEGER NULL REFERENCES cotisations (id)
);

-- Remise des anciennes infos
INSERT INTO membres_categories_tmp SELECT id, nom, description, droit_wiki, droit_membres, 
    droit_compta, droit_inscription, droit_connexion, droit_config, cacher, NULL FROM membres_categories;

-- Suppression de l'ancienne table et renommage de la nouvelle
DROP TABLE membres_categories;
ALTER TABLE membres_categories_tmp RENAME TO membres_categories;

-- Ajout désactivation compte
ALTER TABLE compta_comptes ADD COLUMN desactive INTEGER NOT NULL DEFAULT 0;

PRAGMA foreign_keys = ON;