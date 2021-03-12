-- Remove triggers in case they interact with the migration
DROP TRIGGER IF EXISTS wiki_recherche_delete;
DROP TRIGGER IF EXISTS wiki_recherche_update;
DROP TRIGGER IF EXISTS wiki_recherche_contenu_insert;
DROP TRIGGER IF EXISTS wiki_recherche_contenu_chiffre;

ALTER TABLE membres_categories RENAME TO membres_categories_old;

INSERT OR IGNORE INTO config (cle, valeur) VALUES ('desactiver_site', '0');
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
INSERT INTO files (path, name, type) VALUES ('', 'documents', 2);
INSERT INTO files (path, name, type) VALUES ('', 'config', 2);
INSERT INTO files (path, name, type) VALUES ('', 'transaction', 2);
INSERT INTO files (path, name, type) VALUES ('', 'skel', 2);
INSERT INTO files (path, name, type) VALUES ('', 'user', 2);
INSERT INTO files (path, name, type) VALUES ('', 'web', 2);

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

-- Copy existing files for transactions
INSERT INTO files (path, name, type, mime, modified, size, image)
	SELECT 'transaction/' || t.id, f.nom, 1, f.type, f.datetime, c.taille, f.image
	FROM fichiers f
		INNER JOIN fichiers_contenu c ON c.id = f.id_contenu
		INNER JOIN fichiers_acc_transactions t ON t.fichier = f.id;

INSERT INTO files_contents (id, compressed, content)
	SELECT f2.id, 0, c.contenu
	FROM fichiers f
		INNER JOIN files f2 ON f2.name = f.nom AND f2.path = 'transaction/' || t.id
		INNER JOIN fichiers_contenu c ON c.id = f.id_contenu
		INNER JOIN fichiers_acc_transactions t ON t.fichier = f.id;

-- Copy wiki pages content
CREATE TEMP TABLE wiki_as_files (old_id, new_id, path, content, title, uri,
	old_parent, new_parent, created, modified, author_id, encrypted, type, public);

INSERT INTO wiki_as_files
	SELECT
		id, NULL, '', contenu, titre, uri,
		parent, parent, date_creation, date_modification, id_auteur, chiffrement,
		CASE WHEN (SELECT 1 FROM wiki_pages pp WHERE pp.parent = p.id LIMIT 1) THEN 1 ELSE 2 END, -- Type, 1 = category, 2 = page
		CASE WHEN droit_lecture = -1 THEN 1 ELSE 0 END -- public
	FROM wiki_pages p
	INNER JOIN wiki_revisions r ON r.id_page = p.id AND r.revision = p.revision;

-- Build back path, up to ten levels
UPDATE wiki_as_files AS waf SET
	path = (SELECT uri FROM wiki_as_files WHERE old_id = waf.new_parent) || '/' || path,
	new_parent = (SELECT new_parent FROM wiki_as_files WHERE old_id = waf.new_parent)
	WHERE new_parent > 0;
UPDATE wiki_as_files AS waf SET
	path = (SELECT uri FROM wiki_as_files WHERE old_id = waf.new_parent) || '/' || path,
	new_parent = (SELECT new_parent FROM wiki_as_files WHERE old_id = waf.new_parent)
	WHERE new_parent > 0;
UPDATE wiki_as_files AS waf SET
	path = (SELECT uri FROM wiki_as_files WHERE old_id = waf.new_parent) || '/' || path,
	new_parent = (SELECT new_parent FROM wiki_as_files WHERE old_id = waf.new_parent)
	WHERE new_parent > 0;
UPDATE wiki_as_files AS waf SET
	path = (SELECT uri FROM wiki_as_files WHERE old_id = waf.new_parent) || '/' || path,
	new_parent = (SELECT new_parent FROM wiki_as_files WHERE old_id = waf.new_parent)
	WHERE new_parent > 0;
UPDATE wiki_as_files AS waf SET
	path = (SELECT uri FROM wiki_as_files WHERE old_id = waf.new_parent) || '/' || path,
	new_parent = (SELECT new_parent FROM wiki_as_files WHERE old_id = waf.new_parent)
	WHERE new_parent > 0;
UPDATE wiki_as_files AS waf SET
	path = (SELECT uri FROM wiki_as_files WHERE old_id = waf.new_parent) || '/' || path,
	new_parent = (SELECT new_parent FROM wiki_as_files WHERE old_id = waf.new_parent)
	WHERE new_parent > 0;
UPDATE wiki_as_files AS waf SET
	path = (SELECT uri FROM wiki_as_files WHERE old_id = waf.new_parent) || '/' || path,
	new_parent = (SELECT new_parent FROM wiki_as_files WHERE old_id = waf.new_parent)
	WHERE new_parent > 0;
UPDATE wiki_as_files AS waf SET
	path = (SELECT uri FROM wiki_as_files WHERE old_id = waf.new_parent) || '/' || path,
	new_parent = (SELECT new_parent FROM wiki_as_files WHERE old_id = waf.new_parent)
	WHERE new_parent > 0;
UPDATE wiki_as_files AS waf SET
	path = (SELECT uri FROM wiki_as_files WHERE old_id = waf.new_parent) || '/' || path,
	new_parent = (SELECT new_parent FROM wiki_as_files WHERE old_id = waf.new_parent)
	WHERE new_parent > 0;
UPDATE wiki_as_files AS waf SET
	path = (SELECT uri FROM wiki_as_files WHERE old_id = waf.new_parent) || '/' || path,
	new_parent = (SELECT new_parent FROM wiki_as_files WHERE old_id = waf.new_parent)
	WHERE new_parent > 0;

UPDATE wiki_as_files SET path = TRIM(path, '/');

UPDATE wiki_as_files SET path = NULL WHERE path = '';

-- Copy into files
INSERT INTO files (path, name, type, mime, modified, size)
	SELECT
		'web/' || (CASE WHEN path IS NOT NULL THEN path || '/' ELSE '' END) || uri,
		'index.txt',
		1,
		'text/plain',
		modified,
		0 -- size will be set after
	FROM wiki_as_files;

UPDATE wiki_as_files SET new_id = (SELECT id FROM files WHERE files.path = 'web/' || (CASE WHEN wiki_as_files.path IS NOT NULL THEN wiki_as_files.path || '/' ELSE '' END) || wiki_as_files.uri AND files.name = 'index.txt');

-- x'0a' == \n
INSERT INTO files_contents (id, compressed, content)
	SELECT new_id, 0,
		'Title: ' || title || x'0a' || 'Published: ' || created || x'0a' || 'Status: '
		|| (CASE WHEN public THEN 'Online' ELSE 'Draft' END)
		|| x'0a' || 'Format: ' || (CASE WHEN encrypted THEN 'Skriv/Encrypted' ELSE 'Skriv' END)
		|| x'0a' || x'0a' || '----' || x'0a' || x'0a' || content
	FROM wiki_as_files;

UPDATE wiki_as_files SET new_parent = (SELECT w.new_id FROM wiki_as_files w WHERE w.old_id = wiki_as_files.old_parent);

-- Copy to search
INSERT INTO files_search (path, title, content)
	SELECT
		'web/' || (CASE WHEN path IS NOT NULL THEN path || '/' ELSE '' END) || uri || '/index.txt',
		title,
		CASE WHEN encrypted THEN NULL ELSE content END
	FROM wiki_as_files WHERE encrypted = 0;

-- Copy to web_pages
INSERT INTO web_pages (id, parent, path, file_path, type, status, title, published, modified, format, content)
	SELECT new_id,
	path,
	(CASE WHEN path IS NOT NULL THEN path || '/' ELSE '' END) || uri,
	'web/' || (CASE WHEN path IS NOT NULL THEN path || '/' ELSE '' END) || uri || '/index.txt',
	type,
	CASE WHEN public THEN 'online' ELSE 'draft' END,
	title, created, modified,
	CASE WHEN encrypted THEN 'skriv/encrypted' ELSE 'skriv' END,
	content
	FROM wiki_as_files;

-- Copy files linked to wiki pages
INSERT INTO files (path, name, type, mime, modified, size, image)
	SELECT
		'web/' || (CASE WHEN waf.path IS NOT NULL THEN waf.path || '/' ELSE '' END) || uri,
		f.nom,
		1,
		f.type,
		f.datetime,
		c.taille,
		f.image
	FROM fichiers f
		INNER JOIN fichiers_contenu c ON c.id = f.id_contenu
		INNER JOIN fichiers_wiki_pages w ON w.fichier = f.id
		INNER JOIN wiki_as_files waf ON w.id = waf.old_id;

INSERT INTO files_contents (id, compressed, content)
	SELECT
		f2.id, 0, c.contenu
	FROM fichiers f
		INNER JOIN fichiers_contenu c ON c.id = f.id_contenu
		INNER JOIN fichiers_wiki_pages w ON w.fichier = f.id
		INNER JOIN wiki_as_files waf ON w.id = waf.old_id
		INNER JOIN files AS f2 ON f2.name = f.nom AND f2.path = 'web/' || (CASE WHEN waf.path IS NOT NULL THEN waf.path || '/' ELSE '' END) || waf.uri;

-- Create parent directories
INSERT INTO files (type, path, name)
	SELECT 2,
		CASE WHEN waf.path IS NOT NULL THEN 'web/' || waf.path ELSE 'web' END,
		waf.uri
	FROM wiki_as_files waf
	GROUP BY waf.old_id;

-- Copy existing config files
INSERT INTO files (path, name, type, mime, modified, size, image)
	SELECT 'config', 'admin_bg.png', 1, type, datetime, c.taille, image
	FROM fichiers f
		INNER JOIN fichiers_contenu c ON c.id = f.id_contenu
	WHERE f.id = (SELECT c.value FROM config c WHERE key = 'image_fond') LIMIT 1;

INSERT INTO files_contents (id, compressed, content)
	SELECT f2.id, 0, c.contenu
	FROM files AS f2
		INNER JOIN fichiers f ON f2.name = 'admin_bg.png' AND f2.path = 'config'
		INNER JOIN fichiers_contenu c ON c.id = f.id_contenu
		WHERE f.id = (SELECT c.value FROM config c WHERE key = 'image_fond') LIMIT 1;

-- Rename
UPDATE config SET key = 'admin_background', value = 'config/admin_bg.png' WHERE key = 'image_fond';

-- Copy connection page as a single file
INSERT INTO files (path, name, type, mime, modified, size, image)
	SELECT 'config', 'admin_homepage.skriv', 1, 'text/plain', datetime(), LENGTH(content), 0
	FROM wiki_as_files
	WHERE uri = (SELECT value FROM config WHERE key = 'accueil_connexion');

INSERT INTO files_contents (id, compressed, content)
	SELECT f.id, 0, waf.content
	FROM files f
		INNER JOIN wiki_as_files waf ON waf.uri = (SELECT value FROM config WHERE key = 'accueil_connexion')
	WHERE f.path = 'config' AND f.name = 'admin_homepage.skriv';

-- Rename
UPDATE config SET key = 'admin_homepage', value = 'config/admin_homepage.skriv' WHERE key = 'accueil_connexion';

-- Create transaction directories
INSERT INTO files (path, name, type) SELECT 'transaction', id, 2 FROM fichiers_acc_transactions GROUP BY id;

-- Set file size
UPDATE files SET size = (SELECT LENGTH(content) FROM files_contents WHERE id = files.id) WHERE type = 1;

DELETE FROM plugins_signaux WHERE signal LIKE 'boucle.%';

DROP TABLE wiki_recherche;

DROP TABLE wiki_pages;
DROP TABLE wiki_revisions;

DROP TABLE fichiers_wiki_pages;
DROP TABLE fichiers_acc_transactions;
DROP TABLE fichiers_membres;

DROP TABLE fichiers;
DROP TABLE fichiers_contenu;