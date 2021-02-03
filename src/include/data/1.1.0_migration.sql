-- Remove triggers in case they interact with the migration
DROP TRIGGER IF EXISTS wiki_recherche_delete;
DROP TRIGGER IF EXISTS wiki_recherche_update;
DROP TRIGGER IF EXISTS wiki_recherche_contenu_insert;
DROP TRIGGER IF EXISTS wiki_recherche_contenu_chiffre;

ALTER TABLE membres_categories RENAME TO membres_categories_old;
ALTER TABLE config RENAME TO config_old;

.read 1.1.0_schema.sql

INSERT INTO config SELECT * FROM config_old;
DROP TABLE config_old;

-- This is not used anymore
DELETE FROM config WHERE key = 'version';
DELETE FROM config WHERE key = 'accueil_wiki';

-- New config key
INSERT INTO config (key, value) VALUES ('telephone_asso', NULL);

-- Copy droit_wiki value to droit_web and droit_documents
INSERT INTO users_categories
	SELECT id, nom,
		droit_wiki, -- perm_web
		droit_wiki, -- perm_documents
		droit_membres,
		droit_compta,
		droit_inscription,
		droit_connexion,
		droit_config,
		cacher
	FROM membres_categories_old;

DROP TABLE membres_categories_old;

UPDATE recherches SET contenu = REPLACE(contenu, 'id_categorie', 'category_id') WHERE cible = 'membres' AND contenu LIKE '%id_categorie%';

-- Copy existing file contents
INSERT INTO files_contents (id, hash, content, size)
	SELECT id, hash, contenu, taille FROM fichiers_contenu;

-- Copy existing file metadata for transactions
INSERT INTO files_meta (id, path, name, type, modified, content_id)
	SELECT f.id, 'transaction/' || t.id, nom, type, datetime, id_contenu
	FROM fichiers f
		INNER JOIN fichiers_acc_transactions t ON t.fichier = f.id;

-- Copy wiki pages content
CREATE TEMP TABLE wiki_as_files (old_id, new_id, hash, size, content, name, title, uri,
	old_parent, new_parent, created, modified, author_id, encrypted, content_id, type, public);

INSERT INTO wiki_as_files
	SELECT
		id, NULL, sha1(contenu), LENGTH(contenu), contenu, uri || '.skriv', titre, uri,
		parent, NULL, date_creation, date_modification, id_auteur, chiffrement, 0,
		CASE WHEN (SELECT 1 FROM wiki_pages pp WHERE pp.parent = p.id LIMIT 1) THEN 1 ELSE 2 END, -- Type, 1 = category, 2 = page
		CASE WHEN droit_lecture = -1 THEN 1 ELSE 0 END -- public
	FROM wiki_pages p
	INNER JOIN wiki_revisions r ON r.id_page = p.id AND r.revision = p.revision;

-- Copy into files_contents
INSERT INTO files_contents (hash, content, size) SELECT hash, content, size FROM wiki_as_files;
UPDATE wiki_as_files SET content_id = (SELECT fc.id FROM files_contents fc WHERE fc.hash = wiki_as_files.hash);

-- Copy into files_meta
INSERT INTO files_meta (path, name, type, modified, content_id)
	SELECT
		CASE WHEN public = 1 THEN 'web' ELSE 'documents/wiki' END, -- path
		name,
		CASE WHEN encrypted THEN 'text/vnd.skriv.encrypted' ELSE 'text/vnd.skriv' END,
		modified,
		content_id
	FROM wiki_as_files;

UPDATE wiki_as_files SET new_id = (SELECT id FROM files_meta WHERE name = wiki_as_files.name);
UPDATE wiki_as_files SET new_parent = (SELECT w.new_id FROM wiki_as_files w WHERE w.old_id = wiki_as_files.old_parent);

-- Copy to search
INSERT INTO files_search (id, title, content)
	SELECT new_id, title, content FROM wiki_as_files WHERE encrypted = 0;

-- Copy to web_pages
INSERT INTO web_pages (id, parent_id, path, type, status, uri, title, created)
	SELECT new_id, new_parent, 'web/' || name, type, 1, uri, title, created
	FROM wiki_as_files WHERE public = 1;

-- Copy files linked to wiki pages
INSERT INTO files_meta (path, name, type, modified, content_id)
	SELECT
		(CASE WHEN waf.public = 1 THEN 'web/' ELSE 'wiki/' END) || waf.name || '_files',
		f.nom,
		f.type,
		f.datetime,
		f.id_contenu
	FROM fichiers f
		INNER JOIN fichiers_wiki_pages w ON w.fichier = f.id
		INNER JOIN wiki_as_files waf ON w.id = waf.old_id;

-- Copy existing config files
INSERT INTO files_meta (path, name, type, modified, content_id)
	SELECT 'config', 'admin_bg.png', type, datetime, id_contenu
	FROM fichiers f WHERE id = (SELECT id FROM config WHERE key = 'image_fond') LIMIT 1;

-- Rename
UPDATE config SET key = 'admin_background', value = 'config/admin_bg.png' WHERE key = 'image_fond';

-- Copy connection page as a single file
INSERT INTO files_meta (path, name, type, modified, content_id)
	SELECT 'config', 'admin_homepage.skriv', type, modified, content_id
	FROM files_meta WHERE id = (SELECT new_id FROM wiki_as_files WHERE uri = (SELECT value FROM config WHERE key = 'accueil_connexion'));

-- Rename
UPDATE config SET key = 'admin_homepage', value = 'config/admin_homepage.skriv' WHERE key = 'accueil_connexion';

-- Create directories
INSERT INTO files_meta (path, name, type) VALUES ('documents', 'wiki', 'inode/directory');
INSERT INTO files_meta (path, name, type) SELECT 'transaction', DISTINCT id, 'inode/directory' FROM fichiers_acc_transactions;

-- FIXME: need to update Skriv pages with files/images links

DROP TABLE wiki_recherche;

DROP TABLE wiki_pages;
DROP TABLE wiki_revisions;

DROP TABLE fichiers_wiki_pages;
DROP TABLE fichiers_acc_transactions;
DROP TABLE fichiers_membres;

DROP TABLE fichiers;
DROP TABLE fichiers_contenu;