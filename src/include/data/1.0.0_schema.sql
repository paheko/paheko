CREATE TABLE IF NOT EXISTS config (
-- Configuration de Garradin
    cle TEXT PRIMARY KEY NOT NULL,
    valeur TEXT
);

CREATE TABLE IF NOT EXISTS membres_categories
-- Catégories de membres
(
    id INTEGER PRIMARY KEY NOT NULL,
    nom TEXT NOT NULL,

    droit_wiki INTEGER NOT NULL DEFAULT 1,
    droit_membres INTEGER NOT NULL DEFAULT 1,
    droit_compta INTEGER NOT NULL DEFAULT 1,
    droit_inscription INTEGER NOT NULL DEFAULT 0,
    droit_connexion INTEGER NOT NULL DEFAULT 1,
    droit_config INTEGER NOT NULL DEFAULT 0,
    cacher INTEGER NOT NULL DEFAULT 0
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

    intitule TEXT NOT NULL,
    description TEXT NULL,

    duree INTEGER NULL, -- En jours
    debut TEXT NULL, -- timestamp
    fin TEXT NULL
);

CREATE TABLE IF NOT EXISTS cotisations_tarifs
(
    id INTEGER PRIMARY KEY NOT NULL,
    label TEXT NOT NULL,
    description TEXT NULL,
    amount INTEGER NULL,
    formula TEXT NULL,
    id_account INTEGER NULL REFERENCES acc_accounts (id) ON DELETE SET NULL, -- NULL si le type n'est pas associé automatiquement à la compta
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

CREATE TABLE IF NOT EXISTS membres_mouvements
-- Liaison des enregistrement des paiements en compta
(
    id_membre INTEGER NOT NULL REFERENCES membres (id) ON DELETE CASCADE,
    id_mouvement INTEGER NOT NULL REFERENCES compta_mouvements (id) ON DELETE CASCADE,
    id_cotisation INTEGER NULL REFERENCES cotisations_membres (id) ON DELETE SET NULL,

    PRIMARY KEY (id_membre, id_mouvement)
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

--
-- COMPTA
--

CREATE TABLE IF NOT EXISTS acc_plans
-- Plans comptables : il peut y en avoir plusieurs
(
    id INTEGER NOT NULL PRIMARY KEY,
    country TEXT NOT NULL,
    code TEXT NULL, -- NULL = plan comptable créé par l'utilisateur
    label TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS acc_accounts
-- Comptes des plans comptables
(
    id INTEGER NOT NULL PRIMARY KEY,
    id_plan INTEGER NOT NULL REFERENCES compta_plans,

    code TEXT NOT NULL, -- peut contenir des lettres, eg. 53A, 53B, etc.
    parent INTEGER NULL REFERENCES compta_comptes(id),

    label TEXT NOT NULL,
    description TEXT NULL,

    position INTEGER NOT NULL, -- position actif/passif/charge/produit
    type INTEGER NOT NULL DEFAULT 0, -- Type de compte spécial : banque, caisse, en attente d'encaissement
    bookmark INTEGER NOT NULL DEFAULT 0, -- Signet (ex-catégories): 1 = recette, -1 = dépense
    user INTEGER NOT NULL DEFAULT 1 -- 1 = fait partie du plan comptable original, 0 = a été ajouté par l'utilisateur
);

CREATE UNIQUE INDEX IF NOT EXISTS acc_accounts_codes ON acc_accounts (code, id_plan);
CREATE INDEX IF NOT EXISTS acc_accounts_parent ON acc_accounts (parent);

CREATE TABLE IF NOT EXISTS acc_years
-- Exercices
(
    id INTEGER NOT NULL PRIMARY KEY,

    label TEXT NOT NULL,

    start_date TEXT NOT NULL CHECK (date(debut) IS NOT NULL AND date(debut) = debut),
    end_date TEXT NOT NULL CHECK (date(fin) IS NOT NULL AND date(fin) = fin),

    closed INTEGER NOT NULL DEFAULT 0,

    id_plan INTEGER NOT NULL REFERENCES acc_plans (id)
);

CREATE TABLE IF NOT EXISTS acc_transactions
-- Opérations comptables
(
    id INTEGER PRIMARY KEY NOT NULL,

    label TEXT NOT NULL,
    notes TEXT NULL,
    reference TEXT NULL, -- N° de pièce comptable

    date TEXT NOT NULL DEFAULT CURRENT_DATE CHECK (date(date) IS NOT NULL AND date(date) = date),

    validated INTEGER NOT NULL DEFAULT 0, -- 1 = écriture validée, non modifiable

    hash TEXT NULL,
    prev_hash TEXT NULL,

    id_year INTEGER NOT NULL REFERENCES acc_years(id),
    id_analytical INTEGER NULL REFERENCES acc_accounts(id) ON DELETE SET NULL
);

CREATE INDEX IF NOT EXISTS acc_transactions_year ON acc_transactions (id_year);
CREATE INDEX IF NOT EXISTS acc_transactions_date ON acc_transactions (date);

CREATE TABLE IF NOT EXISTS acc_transactions_lines
-- Lignes d'écritures d'une opération
(
    id INTEGER PRIMARY KEY NOT NULL,

    id_transaction INTEGER NOT NULL REFERENCES acc_transactions (id) ON DELETE CASCADE,
    id_account INTEGER NOT NULL REFERENCES acc_accounts (id), -- N° du compte dans le plan comptable

    credit INTEGER NOT NULL,
    debit INTEGER NOT NULL,

    reconcilied INTEGER NOT NULL DEFAULT 0,
    payment_reference TEXT NULL, -- Référence de paiement, eg. numéro de chèque

    CONSTRAINT line_check1 CHECK ((credit * debit) = 0),
    CONSTRAINT line_check2 CHECK ((credit + debit) > 0)
);

CREATE INDEX IF NOT EXISTS acc_transactions_lines_account ON acc_transactions_lines (id_account);

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
    menu_condition TEXT NULL,
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

CREATE TABLE IF NOT EXISTS fichiers_acc_transactions
-- Associations entre fichiers et journal de compta (pièce comptable par exemple)
(
    fichier INTEGER NOT NULL REFERENCES fichiers (id),
    id INTEGER NOT NULL REFERENCES acc_transactions (id),
    PRIMARY KEY(fichier, id)
);

CREATE TABLE IF NOT EXISTS recherches
-- Recherches enregistrées
(
    id INTEGER NOT NULL PRIMARY KEY,
    id_membre INTEGER NULL REFERENCES membres (id) ON DELETE CASCADE, -- Si non NULL, alors la recherche ne sera visible que par le membre associé
    intitule TEXT NOT NULL,
    creation TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP CHECK (datetime(creation) IS NOT NULL AND datetime(creation) = creation),
    cible TEXT NOT NULL, -- "membres" ou "compta"
    type TEXT NOT NULL, -- "json" ou "sql"
    contenu TEXT NOT NULL
);


CREATE TABLE IF NOT EXISTS compromised_passwords_cache
-- Cache des hash de mots de passe compromis
(
    hash TEXT NOT NULL PRIMARY KEY
);

CREATE TABLE IF NOT EXISTS compromised_passwords_cache_ranges
-- Cache des préfixes de mots de passe compromis
(
    prefix TEXT NOT NULL PRIMARY KEY,
    date INTEGER NOT NULL
);