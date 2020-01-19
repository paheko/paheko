CREATE TABLE IF NOT EXISTS config (
-- Configuration de Garradin
    cle TEXT PRIMARY KEY NOT NULL,
    valeur TEXT
);

-- On stocke ici les ID de catégorie de compta correspondant aux types spéciaux
-- compta_categorie_cotisations => id_categorie
-- compta_categorie_dons => id_categorie

CREATE TABLE IF NOT EXISTS membres_categories
-- Catégories de membres
(
    id INTEGER PRIMARY KEY NOT NULL,
    nom TEXT NOT NULL,
    description TEXT NULL,

    droit_wiki INTEGER NOT NULL DEFAULT 1,
    droit_membres INTEGER NOT NULL DEFAULT 1,
    droit_compta INTEGER NOT NULL DEFAULT 1,
    droit_inscription INTEGER NOT NULL DEFAULT 0,
    droit_connexion INTEGER NOT NULL DEFAULT 1,
    droit_config INTEGER NOT NULL DEFAULT 0,
    cacher INTEGER NOT NULL DEFAULT 0,

    id_cotisation_obligatoire INTEGER NULL REFERENCES cotisations (id) ON DELETE SET NULL
);

-- Membres de l'asso
-- Table dynamique générée par l'application
-- voir Garradin\Membres\Champs.php

CREATE TABLE IF NOT EXISTS membres_sessions
-- Sessions
(
    selecteur TEXT NOT NULL,
    hash TEXT NOT NULL,
    id_membre INTEGER NOT NULL REFERENCES membres (id) ON DELETE CASCADE,
    expire INT NOT NULL,

    PRIMARY KEY (selecteur, id_membre)
);

CREATE TABLE IF NOT EXISTS cotisations
-- Types de cotisations et activités
(
    id INTEGER PRIMARY KEY NOT NULL,
    id_categorie_compta INTEGER NULL, -- NULL si le type n'est pas associé automatiquement à la compta

    intitule TEXT NOT NULL,
    description TEXT NULL,
    montant REAL NOT NULL,

    duree INTEGER NULL, -- En jours
    debut TEXT NULL, -- timestamp
    fin TEXT NULL,

    FOREIGN KEY (id_categorie_compta) REFERENCES compta_categories (id)
);

CREATE TABLE IF NOT EXISTS cotisations_membres
-- Enregistrement des cotisations et activités
(
    id INTEGER NOT NULL PRIMARY KEY,
    id_membre INTEGER NOT NULL REFERENCES membres (id) ON DELETE CASCADE,
    id_cotisation INTEGER NOT NULL REFERENCES cotisations (id) ON DELETE CASCADE,

    date TEXT NOT NULL DEFAULT CURRENT_DATE CHECK (date(date) IS NOT NULL AND date(date) = date)
);

CREATE UNIQUE INDEX IF NOT EXISTS cm_unique ON cotisations_membres (id_membre, id_cotisation, date);

CREATE TABLE IF NOT EXISTS membres_operations
-- Liaision des enregistrement des paiements en compta
(
    id_membre INTEGER NOT NULL REFERENCES membres (id) ON DELETE CASCADE,
    id_operation INTEGER NOT NULL REFERENCES compta_journal (id) ON DELETE CASCADE,
    id_cotisation INTEGER NULL REFERENCES cotisations_membres (id) ON DELETE SET NULL,

    PRIMARY KEY (id_membre, id_operation)
);

CREATE TABLE IF NOT EXISTS rappels
-- Rappels de devoir renouveller une cotisation
(
    id INTEGER NOT NULL PRIMARY KEY,
    id_cotisation INTEGER NOT NULL REFERENCES cotisations (id) ON DELETE CASCADE,

    delai INTEGER NOT NULL, -- Délai en jours pour envoyer le rappel

    sujet TEXT NOT NULL,
    texte TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS rappels_envoyes
-- Enregistrement des rappels envoyés à qui et quand
(
    id INTEGER NOT NULL PRIMARY KEY,

    id_membre INTEGER NOT NULL REFERENCES membres (id) ON DELETE CASCADE,
    id_cotisation INTEGER NOT NULL REFERENCES cotisations (id) ON DELETE CASCADE,
    id_rappel INTEGER NULL REFERENCES rappels (id) ON DELETE CASCADE,

    date TEXT NOT NULL DEFAULT CURRENT_DATE CHECK (date(date) IS NOT NULL AND date(date) = date),

    media INTEGER NOT NULL -- Média utilisé pour le rappel : 1 = email, 2 = courrier, 3 = autre
);

--
-- WIKI
--

CREATE TABLE IF NOT EXISTS wiki_pages
-- Pages du wiki
(
    id INTEGER PRIMARY KEY NOT NULL,
    uri TEXT NOT NULL, -- URI unique (équivalent NomPageWiki)
    titre TEXT NOT NULL,
    date_creation TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP CHECK (datetime(date_creation) IS NOT NULL AND datetime(date_creation) = date_creation),
    date_modification TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP CHECK (datetime(date_modification) IS NOT NULL AND datetime(date_modification) = date_modification),
    parent INTEGER NOT NULL DEFAULT 0, -- ID de la page parent
    revision INTEGER NOT NULL DEFAULT 0, -- Numéro de révision (commence à 0 si pas de texte, +1 à chaque changement du texte)
    droit_lecture INTEGER NOT NULL DEFAULT 0, -- Accès en lecture (-1 = public [site web], 0 = tous ceux qui ont accès en lecture au wiki, 1+ = ID de groupe)
    droit_ecriture INTEGER NOT NULL DEFAULT 0 -- Accès en écriture (0 = tous ceux qui ont droit d'écriture sur le wiki, 1+ = ID de groupe)
);

CREATE UNIQUE INDEX IF NOT EXISTS wiki_uri ON wiki_pages (uri);

CREATE VIRTUAL TABLE IF NOT EXISTS wiki_recherche USING fts4
-- Table dupliquée pour chercher une page
(
    id INT PRIMARY KEY NOT NULL, -- Clé externe obligatoire
    titre TEXT NOT NULL,
    contenu TEXT NULL, -- Contenu de la dernière révision
    FOREIGN KEY (id) REFERENCES wiki_pages(id)
);

CREATE TABLE IF NOT EXISTS wiki_revisions
-- Révisions du contenu des pages
(
    id_page INTEGER NOT NULL REFERENCES wiki_pages (id) ON DELETE CASCADE,
    revision INTEGER NULL,

    id_auteur INTEGER NULL REFERENCES membres (id) ON DELETE SET NULL,

    contenu TEXT NOT NULL,
    modification TEXT NULL, -- Description des modifications effectuées
    chiffrement INTEGER NOT NULL DEFAULT 0, -- 1 si le contenu est chiffré, 0 sinon
    date TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP CHECK (datetime(date) IS NOT NULL AND datetime(date) = date),

    PRIMARY KEY(id_page, revision)
);

CREATE INDEX IF NOT EXISTS wiki_revisions_id_page ON wiki_revisions (id_page);
CREATE INDEX IF NOT EXISTS wiki_revisions_id_auteur ON wiki_revisions (id_auteur);

-- Triggers pour synchro avec table wiki_pages
CREATE TRIGGER IF NOT EXISTS wiki_recherche_delete AFTER DELETE ON wiki_pages
    BEGIN
        DELETE FROM wiki_recherche WHERE id = old.id;
    END;

CREATE TRIGGER IF NOT EXISTS wiki_recherche_update AFTER UPDATE OF id, titre ON wiki_pages
    BEGIN
        UPDATE wiki_recherche SET id = new.id, titre = new.titre WHERE id = old.id;
    END;

-- Trigger pour mettre à jour le contenu de la table de recherche lors d'une nouvelle révision
CREATE TRIGGER IF NOT EXISTS wiki_recherche_contenu_insert AFTER INSERT ON wiki_revisions WHEN new.chiffrement != 1
    BEGIN
        UPDATE wiki_recherche SET contenu = new.contenu WHERE id = new.id_page;
    END;

-- Si le contenu est chiffré, la recherche n'affiche pas de contenu
CREATE TRIGGER IF NOT EXISTS wiki_recherche_contenu_chiffre AFTER INSERT ON wiki_revisions WHEN new.chiffrement = 1
    BEGIN
        UPDATE wiki_recherche SET contenu = '' WHERE id = new.id_page;
    END;

/*
CREATE TABLE wiki_suivi
-- Suivi des pages
(
    id_membre INTEGER NOT NULL,
    id_page INTEGER NOT NULL,

    PRIMARY KEY (id_membre, id_page),

    FOREIGN KEY (id_page) REFERENCES wiki_pages (id), -- Clé externe obligatoire
    FOREIGN KEY (id_membre) REFERENCES membres (id) -- Clé externe obligatoire
);
*/

--
-- COMPTA
--

CREATE TABLE IF NOT EXISTS compta_exercices
-- Exercices
(
    id INTEGER NOT NULL PRIMARY KEY,

    libelle TEXT NOT NULL,

    debut TEXT NOT NULL DEFAULT CURRENT_DATE CHECK (date(debut) IS NOT NULL AND date(debut) = debut),
    fin TEXT NULL DEFAULT NULL CHECK (fin IS NULL OR (date(fin) IS NOT NULL AND date(fin) = fin)),

    cloture INTEGER NOT NULL DEFAULT 0
);


CREATE TABLE IF NOT EXISTS compta_comptes
-- Plan comptable
(
    id TEXT NOT NULL PRIMARY KEY, -- peut contenir des lettres, eg. 53A, 53B, etc.
    parent TEXT NOT NULL DEFAULT 0,

    libelle TEXT NOT NULL,

    position INTEGER NOT NULL, -- position actif/passif/charge/produit
    plan_comptable INTEGER NOT NULL DEFAULT 1, -- 1 = fait partie du plan comptable, 0 = a été ajouté par l'utilisateur
    desactive INTEGER NOT NULL DEFAULT 0 -- 1 = compte historique désactivé
);

CREATE INDEX IF NOT EXISTS compta_comptes_parent ON compta_comptes (parent);

CREATE TABLE IF NOT EXISTS compta_comptes_bancaires
-- Comptes bancaires
(
    id TEXT NOT NULL PRIMARY KEY,

    banque TEXT NOT NULL,

    iban TEXT NULL,
    bic TEXT NULL,

    FOREIGN KEY(id) REFERENCES compta_comptes(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS compta_projets
-- Projets (compta analytique)
(
    id INTEGER PRIMARY KEY NOT NULL,

    libelle TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS compta_journal
-- Journal des opérations comptables
(
    id INTEGER PRIMARY KEY NOT NULL,

    libelle TEXT NOT NULL,
    remarques TEXT NULL,
    numero_piece TEXT NULL, -- N° de pièce comptable

    montant REAL NOT NULL,

    date TEXT NOT NULL DEFAULT CURRENT_DATE CHECK (date(date) IS NOT NULL AND date(date) = date),
    moyen_paiement TEXT NULL,
    numero_cheque TEXT NULL,

    compte_debit TEXT NULL, -- N° du compte dans le plan, NULL est utilisé pour une opération qui vient d'un exercice précédent
    compte_credit TEXT NULL, -- N° du compte dans le plan

    id_exercice INTEGER NULL DEFAULT NULL, -- En cas de compta simple, l'exercice est permanent (NULL)
    id_auteur INTEGER NULL,
    id_categorie INTEGER NULL, -- Numéro de catégorie (en mode simple)
    id_projet INTEGER NULL,

    FOREIGN KEY(moyen_paiement) REFERENCES compta_moyens_paiement(code),
    FOREIGN KEY(compte_debit) REFERENCES compta_comptes(id),
    FOREIGN KEY(compte_credit) REFERENCES compta_comptes(id),
    FOREIGN KEY(id_exercice) REFERENCES compta_exercices(id),
    FOREIGN KEY(id_auteur) REFERENCES membres(id) ON DELETE SET NULL,
    FOREIGN KEY(id_categorie) REFERENCES compta_categories(id) ON DELETE SET NULL,
    FOREIGN KEY(id_projet) REFERENCES compta_projets(id) ON DELETE SET NULL
);

CREATE INDEX IF NOT EXISTS compta_operations_exercice ON compta_journal (id_exercice);
CREATE INDEX IF NOT EXISTS compta_operations_date ON compta_journal (date);
CREATE INDEX IF NOT EXISTS compta_operations_comptes ON compta_journal (compte_debit, compte_credit);
CREATE INDEX IF NOT EXISTS compta_operations_auteur ON compta_journal (id_auteur);

CREATE TABLE IF NOT EXISTS compta_moyens_paiement
-- Moyens de paiement
(
    code TEXT NOT NULL PRIMARY KEY,
    nom TEXT NOT NULL
);

--INSERT INTO compta_moyens_paiement (code, nom) VALUES ('AU', 'Autre');
INSERT OR IGNORE INTO compta_moyens_paiement (code, nom) VALUES ('CB', 'Carte bleue');
INSERT OR IGNORE INTO compta_moyens_paiement (code, nom) VALUES ('CH', 'Chèque');
INSERT OR IGNORE INTO compta_moyens_paiement (code, nom) VALUES ('ES', 'Espèces');
INSERT OR IGNORE INTO compta_moyens_paiement (code, nom) VALUES ('PR', 'Prélèvement');
INSERT OR IGNORE INTO compta_moyens_paiement (code, nom) VALUES ('TI', 'TIP');
INSERT OR IGNORE INTO compta_moyens_paiement (code, nom) VALUES ('VI', 'Virement');

CREATE TABLE IF NOT EXISTS compta_categories
-- Catégories pour simplifier le plan comptable
(
    id INTEGER NOT NULL PRIMARY KEY,
    type INTEGER NOT NULL DEFAULT 1, -- 1 = recette, -1 = dépense, 0 = autre (utilisé uniquement pour l'interface)

    intitule TEXT NOT NULL,
    description TEXT NULL,

    compte TEXT NOT NULL, -- Compte affecté par cette catégorie

    FOREIGN KEY(compte) REFERENCES compta_comptes(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS plugins
(
    id TEXT NOT NULL PRIMARY KEY,
    officiel INTEGER NOT NULL DEFAULT 0,
    nom TEXT NOT NULL,
    description TEXT NULL,
    auteur TEXT NULL,
    url TEXT NULL,
    version TEXT NOT NULL,
    menu INTEGER NOT NULL DEFAULT 0,
    config TEXT NULL
);

CREATE TABLE IF NOT EXISTS plugins_signaux
-- Association entre plugins et signaux (hooks)
(
    signal TEXT NOT NULL,
    plugin TEXT NOT NULL REFERENCES plugins (id),
    callback TEXT NOT NULL,
    PRIMARY KEY (signal, plugin)
);

CREATE TABLE IF NOT EXISTS compta_rapprochement
-- Rapprochement entre compta et relevés de comptes
(
    id_operation INTEGER NOT NULL PRIMARY KEY REFERENCES compta_journal (id) ON DELETE CASCADE,
    date TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP CHECK (datetime(date) IS NOT NULL AND datetime(date) = date),
    id_auteur INTEGER NULL REFERENCES membres (id)
);

CREATE TABLE IF NOT EXISTS fichiers
-- Données sur les fichiers
(
    id INTEGER NOT NULL PRIMARY KEY,
    nom TEXT NOT NULL, -- nom de fichier (par exemple image1234.jpeg)
    type TEXT NULL, -- Type MIME
    image INTEGER NOT NULL DEFAULT 0, -- 1 = image reconnue
    datetime TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP CHECK (datetime(datetime) IS NOT NULL AND datetime(datetime) = datetime), -- Date d'ajout ou mise à jour du fichier
    id_contenu INTEGER NOT NULL REFERENCES fichiers_contenu (id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS fichiers_date ON fichiers (datetime);

CREATE TABLE IF NOT EXISTS fichiers_contenu
-- Contenu des fichiers
(
    id INTEGER NOT NULL PRIMARY KEY,
    hash TEXT NOT NULL, -- Hash SHA1 du contenu du fichier
    taille INTEGER NOT NULL, -- Taille en octets
    contenu BLOB NULL
);

CREATE UNIQUE INDEX IF NOT EXISTS fichiers_hash ON fichiers_contenu (hash);

CREATE TABLE IF NOT EXISTS fichiers_membres
-- Associations entre fichiers et membres (photo de profil par exemple)
(
    fichier INTEGER NOT NULL REFERENCES fichiers (id),
    id INTEGER NOT NULL REFERENCES membres (id),
    PRIMARY KEY(fichier, id)
);

CREATE TABLE IF NOT EXISTS fichiers_wiki_pages
-- Associations entre fichiers et pages du wiki
(
    fichier INTEGER NOT NULL REFERENCES fichiers (id),
    id INTEGER NOT NULL REFERENCES wiki_pages (id),
    PRIMARY KEY(fichier, id)
);

CREATE TABLE IF NOT EXISTS fichiers_compta_journal
-- Associations entre fichiers et journal de compta (pièce comptable par exemple)
(
    fichier INTEGER NOT NULL REFERENCES fichiers (id),
    id INTEGER NOT NULL REFERENCES compta_journal (id),
    PRIMARY KEY(fichier, id)
);
