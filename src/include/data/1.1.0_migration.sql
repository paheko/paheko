-- Copy wiki pages content
CREATE TEMP TABLE wiki_as_files (hash, size, content, name, path, uri, parent, created, modified, author_id, encrypted, content_id);

INSERT INTO wiki_as_files
	SELECT
		sha1(contenu), LENGTH(contenu), contenu,
		uri || '.skriv', NULL, uri, parent,
		date_creation, date_modification, id_auteur, chiffrement, NULL
	FROM wiki_pages p
	INNER JOIN wiki_revisions r ON r.id_page = p.id AND r.revision = p.revision;

-- Build back path, up to ten levels
UPDATE wiki_as_files waf SET
	path = (SELECT uri FROM wiki_as_files WHERE id = waf.parent) || '/' || path,
	parent = (SELECT parent FROM wiki_as_files WHERE id = waf.parent)
	WHERE parent > 0;

-- Would probably be better with a recursive loop but hey, it works like that
UPDATE wiki_as_files waf SET
	path = (SELECT uri FROM wiki_as_files WHERE id = waf.parent) || '/' || path,
	parent = (SELECT parent FROM wiki_as_files WHERE id = waf.parent)
	WHERE parent > 0;
UPDATE wiki_as_files waf SET
	path = (SELECT uri FROM wiki_as_files WHERE id = waf.parent) || '/' || path,
	parent = (SELECT parent FROM wiki_as_files WHERE id = waf.parent)
	WHERE parent > 0;
UPDATE wiki_as_files waf SET
	path = (SELECT uri FROM wiki_as_files WHERE id = waf.parent) || '/' || path,
	parent = (SELECT parent FROM wiki_as_files WHERE id = waf.parent)
	WHERE parent > 0;
UPDATE wiki_as_files waf SET
	path = (SELECT uri FROM wiki_as_files WHERE id = waf.parent) || '/' || path,
	parent = (SELECT parent FROM wiki_as_files WHERE id = waf.parent)
	WHERE parent > 0;
UPDATE wiki_as_files waf SET
	path = (SELECT uri FROM wiki_as_files WHERE id = waf.parent) || '/' || path,
	parent = (SELECT parent FROM wiki_as_files WHERE id = waf.parent)
	WHERE parent > 0;
UPDATE wiki_as_files waf SET
	path = (SELECT uri FROM wiki_as_files WHERE id = waf.parent) || '/' || path,
	parent = (SELECT parent FROM wiki_as_files WHERE id = waf.parent)
	WHERE parent > 0;
UPDATE wiki_as_files waf SET
	path = (SELECT uri FROM wiki_as_files WHERE id = waf.parent) || '/' || path,
	parent = (SELECT parent FROM wiki_as_files WHERE id = waf.parent)
	WHERE parent > 0;
UPDATE wiki_as_files waf SET
	path = (SELECT uri FROM wiki_as_files WHERE id = waf.parent) || '/' || path,
	parent = (SELECT parent FROM wiki_as_files WHERE id = waf.parent)
	WHERE parent > 0;
UPDATE wiki_as_files waf SET
	path = (SELECT uri FROM wiki_as_files WHERE id = waf.parent) || '/' || path,
	parent = (SELECT parent FROM wiki_as_files WHERE id = waf.parent)
	WHERE parent > 0;
UPDATE wiki_as_files waf SET
	path = (SELECT uri FROM wiki_as_files WHERE id = waf.parent) || '/' || path,
	parent = (SELECT parent FROM wiki_as_files WHERE id = waf.parent)
	WHERE parent > 0;

INSERT INTO files_contents (hash, size, content) SELECT hash, size, content FROM wiki_as_files;
UPDATE wiki_as_files SET content_id = (SELECT fc.id FROM files_contents fc WHERE fc.hash = wiki_as_files.hash);

INSERT INTO files_search (id, content) SELECT (content_id, content) FROM wiki_as_files;

INSERT INTO files (path, name, type, created, modified, content_id, author_id)
	SELECT path, name, CASE WHEN encrypted THEN 'text/encrypted' ELSE 'text/skriv' END,
	created, modified, content_id, author_id FROM wiki_as_files;

DROP TRIGGER wiki_recherche_delete;
DROP TRIGGER wiki_recherche_update;
DROP TRIGGER wiki_recherche_contenu_insert;
DROP TRIGGER wiki_recherche_contenu_chiffre;

DROP TABLE wiki_recherche;

DROP TABLE wiki_pages;
DROP TABLE wiki_revisions;