-- Add archived column
ALTER TABLE acc_accounts ADD COLUMN archived INTEGER NOT NULL DEFAULT 0;

-- Delete web pages from files search
DELETE FROM files_search WHERE path LIKE 'web/%' AND path NOT LIKE 'web/%/%';

ALTER TABLE files_search RENAME TO files_search_old;

-- Re-create files search table
CREATE VIRTUAL TABLE IF NOT EXISTS files_search USING fts4
-- Search inside files content
(
	tokenize=unicode61, -- Available from SQLITE 3.7.13 (2012)
	parent,
	name,
	content
);

INSERT INTO files_search (docid, parent, name, content) SELECT rowid, parent, name, NULL FROM files;
REPLACE INTO files_search (docid, parent, name, content)
	SELECT f.rowid, f.parent, f.name, s.content
	FROM files_search_old s
	INNER JOIN files f ON f.rowid = s.docid;
DROP TRIGGER IF EXISTS files_search_bd;
DROP TRIGGER IF EXISTS files_search_ai;
DROP TRIGGER IF EXISTS files_search_au;
DROP TABLE files_search_old;

CREATE TRIGGER IF NOT EXISTS files_search_bd BEFORE DELETE ON files BEGIN
	DELETE FROM files_search WHERE docid = OLD.rowid;
END;

CREATE TRIGGER IF NOT EXISTS files_search_ai AFTER INSERT ON files BEGIN
	INSERT INTO files_search (docid, parent, name, content) VALUES (NEW.rowid, NEW.parent, NEW.name, NULL);
END;

CREATE TRIGGER IF NOT EXISTS files_search_au AFTER UPDATE OF name, parent ON files BEGIN
	UPDATE files_search SET parent = NEW.parent, name = NEW.name WHERE docid = NEW.rowid;
END;

CREATE VIRTUAL TABLE IF NOT EXISTS web_search USING fts4
-- Search inside web pages
(
	tokenize=unicode61, -- Available from SQLITE 3.7.13 (2012)
	title TEXT NOT NULL,
	content TEXT NULL
);

CREATE TRIGGER IF NOT EXISTS web_search_bd BEFORE DELETE ON web_pages BEGIN
	DELETE FROM web_search WHERE docid = OLD.rowid;
END;

CREATE TRIGGER IF NOT EXISTS web_search_au BEFORE UPDATE ON web_pages BEGIN
	DELETE FROM web_search WHERE docid = OLD.rowid;
END;
