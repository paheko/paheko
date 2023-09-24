ALTER TABLE web_pages RENAME TO web_pages_old;
DROP INDEX IF EXISTS web_pages_path;
DROP INDEX IF EXISTS web_pages_dir_path;
DROP INDEX IF EXISTS web_pages_uri;
DROP INDEX IF EXISTS web_pages_parent;
DROP INDEX IF EXISTS web_pages_published;
DROP INDEX IF EXISTS web_pages_title;

CREATE TABLE IF NOT EXISTS web_pages
(
	id INTEGER NOT NULL PRIMARY KEY,
	id_parent INTEGER NULL REFERENCES web_pages(id) ON DELETE CASCADE,
	uri TEXT NOT NULL, -- Page identifier
	type INTEGER NOT NULL, -- 1 = Category, 2 = Page
	status TEXT NOT NULL,
	format TEXT NOT NULL,
	published TEXT NOT NULL CHECK (datetime(published) IS NOT NULL AND datetime(published) = published),
	modified TEXT NOT NULL CHECK (datetime(modified) IS NOT NULL AND datetime(modified) = modified),
	title TEXT NOT NULL,
	content TEXT NOT NULL
);

CREATE UNIQUE INDEX IF NOT EXISTS web_pages_uri ON web_pages (uri);
CREATE INDEX IF NOT EXISTS web_pages_id_parent ON web_pages (id_parent);
CREATE INDEX IF NOT EXISTS web_pages_published ON web_pages (published);
CREATE INDEX IF NOT EXISTS web_pages_title ON web_pages (title);

INSERT INTO web_pages SELECT
	id,
	(SELECT id FROM web_pages_old WHERE path = a.parent),
	uri,
	type,
	status,
	format,
	published,
	modified,
	title,
	content
	FROM web_pages_old AS a;
