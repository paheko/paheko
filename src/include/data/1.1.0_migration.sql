ALTER TABLE membres_categories RENAME TO membres_categories_old;

.read 1.1.0_schema.sql

INSERT INTO files_contents (id, hash, content, size)
	SELECT id, hash, contenu, taille FROM fichiers_contenu;

INSERT INTO files (id, hash, folder_id, name, type, created, author_id, public, image)
	SELECT f.id, c.hash, NULL, nom, type, datetime, NULL, 0, image FROM fichiers f INNER JOIN fichiers_contenu c ON c.id = f.id_contenu;

INSERT INTO membres_categories
	SELECT id, nom,
		droit_wiki, -- droit_web
		droit_wiki, -- droit_documents
		droit_membres,
		droit_compta,
		droit_inscription,
		droit_connexion,
		droit_config,
		cacher
	FROM membres_categories_old;

DROP TABLE membres_categories_old;

-- Copy wiki pages content
CREATE TEMP TABLE wiki_as_files (old_id, new_id, hash, size, content, name, title, uri, old_parent, new_parent, created, modified, author_id, encrypted, image, content_id, type, public);

INSERT INTO wiki_as_files
	SELECT
		id, NULL, sha1(contenu), LENGTH(contenu), contenu,
		uri || '.skriv', titre, uri, parent, NULL,
		date_creation, date_modification, id_auteur, chiffrement, 0, NULL,
		CASE WHEN (SELECT 1 FROM wiki_pages pp WHERE pp.parent = p.id LIMIT 1) THEN 1 ELSE 2 END, -- Type, 1 = category, 2 = page
		CASE WHEN droit_lecture = -1 THEN 1 ELSE 0 END -- public
	FROM wiki_pages p
	INNER JOIN wiki_revisions r ON r.id_page = p.id AND r.revision = p.revision;

UPDATE wiki_as_files SET name = 'index.skriv' WHERE type = 1;

-- Build back path, up to ten levels
--UPDATE wiki_as_files waf SET
--	path = (SELECT uri FROM wiki_as_files WHERE id = waf.parent) || '/' || path,
--	parent = (SELECT parent FROM wiki_as_files WHERE id = waf.parent)
--	WHERE parent > 0;

-- Create private folders
INSERT INTO files_folders (id, parent_id, name, system)
	SELECT old_id, old_parent, uri, 0 FROM wiki_as_files WHERE type = 1;

-- Create web folders
INSERT INTO files_folders (id, parent_id, name, system)
	SELECT old_id + 10000, old_parent + 10000, uri, 1 FROM wiki_as_files WHERE type = 1;

UPDATE files_folders SET parent_id = (SELECT CASE WHEN f.system = 0 THEN f.id ELSE f.id + 10000 END FROM files_folders f WHERE f.id = parent_id);

INSERT INTO files_contents (hash, content, size) SELECT hash, content, size FROM wiki_as_files;
UPDATE wiki_as_files SET content_id = (SELECT fc.id FROM files_contents fc WHERE fc.hash = wiki_as_files.hash);

INSERT INTO files (hash, folder_id, name, type, created, author_id, public, image)
	SELECT
		hash,
		(SELECT CASE WHEN public = 0 THEN f.id ELSE f.id + 10000 END FROM files_folders f WHERE f.id = old_parent),
		name,
		CASE WHEN encrypted THEN 'text/vnd.skriv.encrypted' ELSE 'text/vnd.skriv' END,
		created,
		author_id,
		public,
		image
	FROM wiki_as_files;

INSERT INTO files_search (id, content) SELECT new_id, content FROM wiki_as_files WHERE encrypted = 0;

UPDATE wiki_as_files SET new_id = (SELECT id FROM files WHERE hash = wiki_as_files.hash);
UPDATE wiki_as_files SET new_parent = (SELECT w.new_id FROM wiki_as_files w WHERE w.old_id = wiki_as_files.old_parent);

INSERT INTO web_pages
	SELECT new_id, new_parent, type, 1, uri, title, modified FROM wiki_as_files WHERE public = 1;

INSERT INTO files_links (id, web_page_id)
	SELECT
		id,
		id
	FROM web_pages
	WHERE status = 1;

INSERT INTO files_links (id, file_id)
	SELECT w.fichier, waf.new_id
		FROM fichiers_wiki_pages w
		INNER JOIN wiki_as_files waf ON waf.old_id = w.id
		INNER JOIN files f ON f.hash = waf.hash;

INSERT INTO files_links (id, transaction_id)
	SELECT fichier, id FROM fichiers_acc_transactions;

INSERT INTO files_links (id, config)
	SELECT valeur, cle FROM config WHERE cle = 'image_fond' AND valeur > 0;

UPDATE files SET public = 1 WHERE id = (SELECT valeur FROM config WHERE cle = 'image_fond');

-- Copy connection page
INSERT INTO files (hash, folder_id, name, type, created, author_id, public)
	SELECT hash, NULL, 'Accueil_connexion.skriv', type, created, author_id, 0
	FROM files WHERE id = (SELECT new_id FROM wiki_as_files WHERE uri = (SELECT valeur FROM config WHERE cle = 'accueil_connexion'));

UPDATE config SET valeur = (SELECT id FROM files WHERE name = 'Accueil_connexion.skriv') WHERE cle = 'accueil_connexion';
UPDATE config SET cle = 'admin_homepage' WHERE cle = 'accueil_connexion';
DELETE FROM config WHERE cle = 'accueil_wiki';
INSERT INTO config (cle, valeur) VALUES ('telephone_asso', NULL);

DROP TRIGGER wiki_recherche_delete;
DROP TRIGGER wiki_recherche_update;
DROP TRIGGER wiki_recherche_contenu_insert;
DROP TRIGGER wiki_recherche_contenu_chiffre;

DROP TABLE wiki_recherche;

DROP TABLE wiki_pages;
DROP TABLE wiki_revisions;

DROP TABLE fichiers_wiki_pages;
DROP TABLE fichiers_acc_transactions;
DROP TABLE fichiers_membres;

DROP TABLE fichiers;
DROP TABLE fichiers_contenu;