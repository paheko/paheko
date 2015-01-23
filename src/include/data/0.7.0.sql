CREATE TABLE plugins_signaux
-- Association entre plugins et signaux (hooks)
(
    signal TEXT NOT NULL,
    plugin TEXT NOT NULL REFERENCES plugins (id),
    callback TEXT NOT NULL,
    PRIMARY KEY (signal, plugin)
);

CREATE TABLE fichiers
-- Données sur les fichiers
(
	id INTEGER NOT NULL PRIMARY KEY,
	nom TEXT NOT NULL, -- nom de fichier (par exemple image1234.jpeg)
	type TEXT NOT NULL, -- Type MIME
	titre TEXT NOT NULL, -- Titre/description
	date TEXT NOT NULL DEFAULT CURRENT_DATE, -- Date d'ajout ou mise à jour du fichier
	hash TEXT NOT NULL, -- Hash SHA1 du contenu du fichier
	taille INTEGER NOT NULL -- Taille en octets
);

CREATE UNIQUE INDEX fichiers_hash ON fichiers (hash);
CREATE INDEX fichiers_titre ON fichiers (titre);
CREATE INDEX fichiers_date ON fichiers (date);

CREATE TABLE fichiers_contenu
-- Contenu des fichiers
(
	id INTEGER NOT NULL PRIMARY KEY REFERENCES fichiers (id),
	contenu BLOB
);

CREATE TABLE fichiers_membres
-- Associations entre fichiers et membres (photo de profil par exemple)
(
	fichier INTEGER NOT NULL REFERENCES fichiers (id),
	id INTEGER NOT NULL REFERENCES membres (id)
);

CREATE TABLE fichiers_wiki_pages
-- Associations entre fichiers et pages du wiki
(
	fichier INTEGER NOT NULL REFERENCES fichiers (id),
	id INTEGER NOT NULL REFERENCES wiki_pages (id)
);

CREATE TABLE fichiers_compta_journal
-- Associations entre fichiers et journal de compta (pièce comptable par exemple)
(
	fichier INTEGER NOT NULL REFERENCES fichiers (id),
	id INTEGER NOT NULL REFERENCES compta_journal (id)
);
