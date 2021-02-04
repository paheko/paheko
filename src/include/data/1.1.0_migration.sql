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

-- Create directories
INSERT INTO files (path, name, type) VALUES ('documents', 'wiki', 'inode/directory');
INSERT INTO files (path, name, type) VALUES ('', 'documents', 'inode/directory');
INSERT INTO files (path, name, type) VALUES ('', 'config', 'inode/directory');
INSERT INTO files (path, name, type) VALUES ('', 'transaction', 'inode/directory');
INSERT INTO files (path, name, type) VALUES ('', 'skel', 'inode/directory');
INSERT INTO files (path, name, type) VALUES ('', 'user', 'inode/directory');
INSERT INTO files (path, name, type) VALUES ('', 'web', 'inode/directory');

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

-- Copy existing file metadata for transactions
INSERT INTO files (path, name, type, modified, size, content)
	SELECT 'transaction/' || t.id, f.nom, f.type, f.datetime, c.taille, c.contenu
	FROM fichiers f
		INNER JOIN fichiers_contenu c ON c.id = f.id_contenu
		INNER JOIN fichiers_acc_transactions t ON t.fichier = f.id;

-- Copy wiki pages content
CREATE TEMP TABLE wiki_as_files (old_id, new_id, hash, size, content, name, title, uri,
	old_parent, new_parent, created, modified, author_id, encrypted, type, public);

INSERT INTO wiki_as_files
	SELECT
		id, NULL, sha1(contenu), LENGTH(contenu), contenu, uri || '.skriv', titre, uri,
		parent, NULL, date_creation, date_modification, id_auteur, chiffrement,
		CASE WHEN (SELECT 1 FROM wiki_pages pp WHERE pp.parent = p.id LIMIT 1) THEN 1 ELSE 2 END, -- Type, 1 = category, 2 = page
		CASE WHEN droit_lecture = -1 THEN 1 ELSE 0 END -- public
	FROM wiki_pages p
	INNER JOIN wiki_revisions r ON r.id_page = p.id AND r.revision = p.revision;

-- Copy into files
INSERT INTO files (path, name, type, modified, size, content)
	SELECT
		CASE WHEN public = 1 THEN 'web' ELSE 'documents/wiki' END, -- path
		name,
		'text/plain',
		modified,
		LENGTH(content),
		content
	FROM wiki_as_files;

UPDATE wiki_as_files SET new_id = (SELECT id FROM files WHERE name = wiki_as_files.name);
UPDATE wiki_as_files SET new_parent = (SELECT w.new_id FROM wiki_as_files w WHERE w.old_id = wiki_as_files.old_parent);

-- Copy to search
INSERT INTO files_search (id, title, content)
	SELECT new_id, title, content FROM wiki_as_files WHERE encrypted = 0;

-- Copy to web_pages
INSERT INTO web_pages (id, parent_id, path, type, status, uri, title, created)
	SELECT new_id, new_parent, 'web/' || name, type, 1, uri, title, created
	FROM wiki_as_files WHERE public = 1;

-- Copy files linked to wiki pages
INSERT INTO files (path, name, type, modified, size, content)
	SELECT
		(CASE WHEN waf.public = 1 THEN 'web/' ELSE 'documents/wiki/' END) || waf.uri || '_files',
		f.nom,
		f.type,
		f.datetime,
		c.taille,
		c.contenu
	FROM fichiers f
		INNER JOIN fichiers_contenu c ON c.id = f.id_contenu
		INNER JOIN fichiers_wiki_pages w ON w.fichier = f.id
		INNER JOIN wiki_as_files waf ON w.id = waf.old_id;

-- Create parent directories
INSERT INTO files (type, path, name)
	SELECT 'inode/directory',
		CASE WHEN waf.public = 1 THEN 'web' ELSE 'documents/wiki' END,
		waf.uri || '_files'
	FROM fichiers f
		INNER JOIN fichiers_wiki_pages w ON w.fichier = f.id
		INNER JOIN wiki_as_files waf ON w.id = waf.old_id
	GROUP BY waf.old_id;

-- Copy existing config files
INSERT INTO files (path, name, type, modified, size, content)
	SELECT 'config', 'admin_bg.png', type, datetime, c.taille, c.contenu
	FROM fichiers f
		INNER JOIN fichiers_contenu c ON c.id = f.id_contenu
	WHERE f.id = (SELECT c.id FROM config c WHERE key = 'image_fond') LIMIT 1;

-- Rename
UPDATE config SET key = 'admin_background', value = 'config/admin_bg.png' WHERE key = 'image_fond';

-- Copy connection page as a single file
INSERT INTO files (path, name, type, modified, size, content)
	SELECT 'config', 'admin_homepage.skriv', type, modified, size, content
	FROM files WHERE id = (SELECT new_id FROM wiki_as_files WHERE uri = (SELECT value FROM config WHERE key = 'accueil_connexion'));

-- Rename
UPDATE config SET key = 'admin_homepage', value = 'config/admin_homepage.skriv' WHERE key = 'accueil_connexion';

-- Create directories
INSERT INTO files (path, name, type) SELECT 'transaction', id, 'inode/directory' FROM fichiers_acc_transactions GROUP BY id;

DROP TABLE wiki_recherche;

DROP TABLE wiki_pages;
DROP TABLE wiki_revisions;

DROP TABLE fichiers_wiki_pages;
DROP TABLE fichiers_acc_transactions;
DROP TABLE fichiers_membres;

DROP TABLE fichiers;
DROP TABLE fichiers_contenu;