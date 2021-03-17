-- Remove triggers in case they interact with the migration
DROP TRIGGER IF EXISTS wiki_recherche_delete;
DROP TRIGGER IF EXISTS wiki_recherche_update;
DROP TRIGGER IF EXISTS wiki_recherche_contenu_insert;
DROP TRIGGER IF EXISTS wiki_recherche_contenu_chiffre;

-- Fix some rare edge cases where date_inscription is incorrect
UPDATE membres SET date_inscription = date() WHERE date(date_inscription) IS NULL;

-- Uh, force another login id if email is not correct
UPDATE config SET valeur = 'numero' WHERE cle = 'champ_identifiant' AND valeur = 'email'
	AND (SELECT COUNT(*) FROM membres GROUP BY LOWER(email) HAVING COUNT(*) > 1 LIMIT 1);

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
INSERT INTO files (parent, name, path, type) VALUES ('', 'documents', 'documents', 2);
INSERT INTO files (parent, name, path, type) VALUES ('', 'config', 'config', 2);
INSERT INTO files (parent, name, path, type) VALUES ('', 'transaction', 'transaction', 2);
INSERT INTO files (parent, name, path, type) VALUES ('', 'skel', 'skel', 2);
INSERT INTO files (parent, name, path, type) VALUES ('', 'user', 'user', 2);
INSERT INTO files (parent, name, path, type) VALUES ('', 'web', 'web', 2);

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

UPDATE recherches SET contenu = REPLACE(contenu, 'id_categorie', 'id_category') WHERE cible = 'membres' AND contenu LIKE '%id_categorie%';

CREATE TEMP TABLE files_transactions (old_id, old_transaction, old_name, new_path, new_id, same_name);

-- Adding an extra step as some file names can have the same name
INSERT INTO files_transactions
	SELECT f.id, t.id, f.nom, NULL, NULL, NULL
	FROM fichiers f
		INNER JOIN fichiers_acc_transactions t ON t.fichier = f.id;

UPDATE files_transactions SET same_name = old_id || '_'
	WHERE old_id IN (SELECT old_id FROM files_transactions GROUP BY old_transaction, old_name HAVING COUNT(*) > 1);

-- Make file name is unique!
UPDATE files_transactions SET new_path = 'transaction/' || old_transaction || '/' || COALESCE(old_id || '_', '') || old_name;

-- Copy existing files for transactions
INSERT INTO files (path, parent, name, type, mime, modified, size, image)
	SELECT
		ft.new_path,
		dirname(ft.new_path),
		basename(ft.new_path),
		1, f.type, f.datetime, c.taille, f.image
	FROM files_transactions ft
		INNER JOIN fichiers f ON f.id = ft.old_id
		INNER JOIN fichiers_contenu c ON c.id = f.id_contenu;

UPDATE files_transactions SET new_id = (SELECT id FROM files WHERE path = new_path);

INSERT INTO files_contents (id, compressed, content)
	SELECT ft.new_id, 0, c.contenu
	FROM fichiers f
		INNER JOIN files_transactions ft ON ft.old_id = f.id
		INNER JOIN fichiers_contenu c ON c.id = f.id_contenu;

-- Copy wiki pages content
CREATE TEMP TABLE wiki_as_files (old_id, new_id, path, content, title, uri,
	old_parent, new_parent, created, modified, author_id, encrypted, type, public);

INSERT INTO wiki_as_files
	SELECT
		id, NULL, '', CASE WHEN contenu IS NULL THEN '' ELSE contenu END, titre, uri,
		parent, parent, date_creation, date_modification, id_auteur, chiffrement,
		CASE WHEN (SELECT 1 FROM wiki_pages pp WHERE pp.parent = p.id LIMIT 1) THEN 1 ELSE 2 END, -- Type, 1 = category, 2 = page
		CASE WHEN droit_lecture = -1 THEN 1 ELSE 0 END -- public
	FROM wiki_pages p
	LEFT JOIN wiki_revisions r ON r.id_page = p.id AND r.revision = p.revision;

-- Build path
WITH RECURSIVE path(level, uri, parent, id) AS (
	SELECT 0, uri, old_parent, old_id
	FROM wiki_as_files
	UNION ALL
	SELECT path.level + 1,
	wiki_as_files.uri,
	wiki_as_files.old_parent,
	path.id
	FROM wiki_as_files
	JOIN path ON wiki_as_files.old_id = path.parent
),
path_from_root AS (
	SELECT group_concat(uri, '/') AS path, id
	FROM (SELECT id, uri FROM path ORDER BY level DESC)
	GROUP BY id
)
UPDATE wiki_as_files SET path = (SELECT path FROM path_from_root WHERE id = wiki_as_files.old_id);

-- Copy into files
INSERT INTO files (path, parent, name, type, mime, modified, size)
	SELECT
		'web/' || path || '/index.txt',
		'web/' || path,
		'index.txt',
		1,
		'text/plain',
		modified,
		0 -- size will be set after
	FROM wiki_as_files;

UPDATE wiki_as_files SET new_id = (SELECT id FROM files WHERE files.path = 'web/' || (CASE WHEN wiki_as_files.path IS NOT NULL THEN wiki_as_files.path || '/' ELSE '' END) || wiki_as_files.uri || '/index.txt');

-- x'0a' == \n
INSERT INTO files_contents (id, compressed, content)
	SELECT f.id, 0,
		'Title: ' || title || x'0a' || 'Published: ' || created || x'0a' || 'Status: '
		|| (CASE WHEN public THEN 'Online' ELSE 'Draft' END)
		|| x'0a' || 'Format: ' || (CASE WHEN encrypted THEN 'Skriv/Encrypted' ELSE 'Skriv' END)
		|| x'0a' || x'0a' || '----' || x'0a' || x'0a' || content
	FROM wiki_as_files waf
	INNER JOIN files f ON f.path = 'web/' || waf.path || '/index.txt';

-- Copy to search
INSERT INTO files_search (path, title, content)
	SELECT
		'web/' || path || '/index.txt',
		title,
		CASE WHEN encrypted THEN NULL ELSE content END
	FROM wiki_as_files WHERE encrypted = 0;

-- Copy to web_pages
INSERT INTO web_pages (id, parent, path, file_path, type, status, title, published, modified, format, content)
	SELECT new_id,
	CASE WHEN dirname(path) = '.' THEN '' ELSE dirname(path) END,
	path,
	'web/' || path || '/index.txt',
	type,
	CASE WHEN public THEN 'online' ELSE 'draft' END,
	title, created, modified,
	CASE WHEN encrypted THEN 'skriv/encrypted' ELSE 'skriv' END,
	content
	FROM wiki_as_files;

CREATE TEMP TABLE files_wiki (old_id, wiki_id, web_path, old_name, new_path, new_id);

-- Adding an extra step as some file names can have the same name
INSERT INTO files_wiki
	SELECT f.id, w.id, waf.path, f.nom, 'web/' || waf.path || '/' || f.id || '_' || f.nom, NULL
	FROM fichiers f
		INNER JOIN fichiers_wiki_pages w ON w.fichier = f.id
		INNER JOIN wiki_as_files waf ON w.id = waf.old_id;

-- Copy files linked to wiki pages
INSERT INTO files (path, parent, name, type, mime, modified, size, image)
	SELECT
		fw.new_path,
		dirname(fw.new_path),
		basename(fw.new_path),
		1,
		f.type,
		f.datetime,
		c.taille,
		f.image
	FROM fichiers f
		INNER JOIN fichiers_contenu c ON c.id = f.id_contenu
		INNER JOIN files_wiki fw ON fw.old_id = f.id;

UPDATE files_wiki SET new_id = (SELECT id FROM files WHERE path = new_path);

INSERT INTO files_contents (id, compressed, content)
	SELECT
		fw.new_id, 0, c.contenu
	FROM files_wiki fw
		INNER JOIN fichiers f ON f.id = fw.old_id
		INNER JOIN fichiers_contenu c ON c.id = f.id_contenu;

-- Create parent directories
INSERT INTO files (type, path, parent, name)
	SELECT 2,
		'web/' || waf.path,
		dirname('web/' || waf.path),
		waf.uri
	FROM wiki_as_files waf;

INSERT OR IGNORE INTO files (type, path, parent, name) SELECT 2, parent, dirname(parent), basename(parent) FROM files WHERE type = 2 AND dirname(parent) != '.' AND dirname(parent) != '' AND (SELECT 1 FROM files f2 WHERE f2.path = dirname(files.parent) LIMIT 1) IS NULL;
INSERT OR IGNORE INTO files (type, path, parent, name) SELECT 2, parent, dirname(parent), basename(parent) FROM files WHERE type = 2 AND dirname(parent) != '.' AND dirname(parent) != '' AND (SELECT 1 FROM files f2 WHERE f2.path = dirname(files.parent) LIMIT 1) IS NULL;
INSERT OR IGNORE INTO files (type, path, parent, name) SELECT 2, parent, dirname(parent), basename(parent) FROM files WHERE type = 2 AND dirname(parent) != '.' AND dirname(parent) != '' AND (SELECT 1 FROM files f2 WHERE f2.path = dirname(files.parent) LIMIT 1) IS NULL;
INSERT OR IGNORE INTO files (type, path, parent, name) SELECT 2, parent, dirname(parent), basename(parent) FROM files WHERE type = 2 AND dirname(parent) != '.' AND dirname(parent) != '' AND (SELECT 1 FROM files f2 WHERE f2.path = dirname(files.parent) LIMIT 1) IS NULL;
INSERT OR IGNORE INTO files (type, path, parent, name) SELECT 2, parent, dirname(parent), basename(parent) FROM files WHERE type = 2 AND dirname(parent) != '.' AND dirname(parent) != '' AND (SELECT 1 FROM files f2 WHERE f2.path = dirname(files.parent) LIMIT 1) IS NULL;
INSERT OR IGNORE INTO files (type, path, parent, name) SELECT 2, parent, dirname(parent), basename(parent) FROM files WHERE type = 2 AND dirname(parent) != '.' AND dirname(parent) != '' AND (SELECT 1 FROM files f2 WHERE f2.path = dirname(files.parent) LIMIT 1) IS NULL;
INSERT OR IGNORE INTO files (type, path, parent, name) SELECT 2, parent, dirname(parent), basename(parent) FROM files WHERE type = 2 AND dirname(parent) != '.' AND dirname(parent) != '' AND (SELECT 1 FROM files f2 WHERE f2.path = dirname(files.parent) LIMIT 1) IS NULL;
INSERT OR IGNORE INTO files (type, path, parent, name) SELECT 2, parent, dirname(parent), basename(parent) FROM files WHERE type = 2 AND dirname(parent) != '.' AND dirname(parent) != '' AND (SELECT 1 FROM files f2 WHERE f2.path = dirname(files.parent) LIMIT 1) IS NULL;
INSERT OR IGNORE INTO files (type, path, parent, name) SELECT 2, parent, dirname(parent), basename(parent) FROM files WHERE type = 2 AND dirname(parent) != '.' AND dirname(parent) != '' AND (SELECT 1 FROM files f2 WHERE f2.path = dirname(files.parent) LIMIT 1) IS NULL;
INSERT OR IGNORE INTO files (type, path, parent, name) SELECT 2, parent, dirname(parent), basename(parent) FROM files WHERE type = 2 AND dirname(parent) != '.' AND dirname(parent) != '' AND (SELECT 1 FROM files f2 WHERE f2.path = dirname(files.parent) LIMIT 1) IS NULL;

-- Copy existing config files
INSERT INTO files (path, parent, name, type, mime, modified, size, image)
	SELECT 'config/admin_bg.png', 'config', 'admin_bg.png', 1, type, datetime, c.taille, image
	FROM fichiers f
		INNER JOIN fichiers_contenu c ON c.id = f.id_contenu
	WHERE f.id = (SELECT c.value FROM config c WHERE key = 'image_fond') LIMIT 1;

INSERT INTO files_contents (id, compressed, content)
	SELECT f2.id, 0, c.contenu
	FROM files AS f2
		INNER JOIN fichiers f ON f2.path = 'config/admin_bg.png'
		INNER JOIN fichiers_contenu c ON c.id = f.id_contenu
		WHERE f.id = (SELECT c.value FROM config c WHERE key = 'image_fond') LIMIT 1;

-- Rename
UPDATE config SET key = 'admin_background', value = 'config/admin_bg.png' WHERE key = 'image_fond';

-- Copy connection page as a single file
INSERT INTO files (path, parent, name, type, mime, modified, size, image)
	SELECT 'config/admin_homepage.skriv', 'config', 'admin_homepage.skriv', 1, 'text/plain', datetime(), LENGTH(content), 0
	FROM wiki_as_files
	WHERE uri = (SELECT value FROM config WHERE key = 'accueil_connexion');

INSERT INTO files_contents (id, compressed, content)
	SELECT f.id, 0, waf.content
	FROM files f
		INNER JOIN wiki_as_files waf ON waf.uri = (SELECT value FROM config WHERE key = 'accueil_connexion')
	WHERE f.path = 'config/admin_homepage.skriv';

-- Rename
UPDATE config SET key = 'admin_homepage', value = 'config/admin_homepage.skriv' WHERE key = 'accueil_connexion';
UPDATE config SET key = 'site_disabled' WHERE key = 'desactiver_site';

-- Create transaction directories
INSERT INTO files (path, parent, name, type) SELECT 'transaction/' || id, 'transaction', id, 2 FROM fichiers_acc_transactions GROUP BY id;

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