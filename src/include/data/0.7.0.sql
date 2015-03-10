CREATE TABLE plugins_signaux
-- Association entre plugins et signaux (hooks)
(
    signal TEXT NOT NULL,
    plugin TEXT NOT NULL REFERENCES plugins (id),
    callback TEXT NOT NULL,
    PRIMARY KEY (signal, plugin)
);

CREATE TABLE compta_rapprochement
-- Rapprochement entre compta et relevés de comptes
(
    operation INTEGER NOT NULL PRIMARY KEY REFERENCES compta_journal (id),
    date TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    auteur INTEGER NOT NULL REFERENCES membres (id)
);

CREATE TABLE fichiers
-- Données sur les fichiers
(
    id INTEGER NOT NULL PRIMARY KEY,
    nom TEXT NOT NULL, -- nom de fichier (par exemple image1234.jpeg)
    type TEXT NULL, -- Type MIME
    image INTEGER NOT NULL DEFAULT 0, -- 1 = image reconnue
    datetime TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP, -- Date d'ajout ou mise à jour du fichier
    id_contenu INTEGER NOT NULL REFERENCES fichiers_contenu (id)
);

CREATE INDEX fichiers_date ON fichiers (datetime);

CREATE TABLE fichiers_contenu
-- Contenu des fichiers
(
    id INTEGER NOT NULL PRIMARY KEY,
    hash TEXT NOT NULL, -- Hash SHA1 du contenu du fichier
    taille INTEGER NOT NULL, -- Taille en octets
    contenu BLOB NULL
);

CREATE UNIQUE INDEX fichiers_hash ON fichiers_contenu (hash);

CREATE TABLE fichiers_membres
-- Associations entre fichiers et membres (photo de profil par exemple)
(
    fichier INTEGER NOT NULL REFERENCES fichiers (id),
    id INTEGER NOT NULL REFERENCES membres (id),
    PRIMARY KEY(fichier, id)
);

CREATE TABLE fichiers_wiki_pages
-- Associations entre fichiers et pages du wiki
(
    fichier INTEGER NOT NULL REFERENCES fichiers (id),
    id INTEGER NOT NULL REFERENCES wiki_pages (id),
    PRIMARY KEY(fichier, id)
);

CREATE TABLE fichiers_compta_journal
-- Associations entre fichiers et journal de compta (pièce comptable par exemple)
(
    fichier INTEGER NOT NULL REFERENCES fichiers (id),
    id INTEGER NOT NULL REFERENCES compta_journal (id),
    PRIMARY KEY(fichier, id)
);