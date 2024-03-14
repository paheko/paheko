ALTER TABLE files RENAME TO files_old;

CREATE TABLE IF NOT EXISTS files
-- Files metadata
(
	id INTEGER NOT NULL PRIMARY KEY,
	hash_id TEXT NOT NULL,
	path TEXT NOT NULL,
	parent TEXT NULL REFERENCES files(path) ON DELETE CASCADE ON UPDATE CASCADE,
	name TEXT NOT NULL, -- File name
	type INTEGER NOT NULL, -- File type, 1 = file, 2 = directory
	mime TEXT NULL,
	size INT NULL,
	modified TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP CHECK (datetime(modified) IS NOT NULL AND datetime(modified) = modified),
	image INT NOT NULL DEFAULT 0,
	md5 TEXT NULL,
	trash TEXT NULL CHECK (datetime(trash) IS NULL OR datetime(trash) = trash),

	CHECK (type = 2 OR (mime IS NOT NULL AND size IS NOT NULL))
);

INSERT INTO files SELECT id, random_string(12), path, parent, name, type, mime, size, modified, image, md5, trash FROM files_old;
DROP TABLE files_old;

-- Unique index as this is used to make up a file path
CREATE UNIQUE INDEX IF NOT EXISTS files_unique ON files (path);
CREATE UNIQUE INDEX IF NOT EXISTS files_unique_hash ON files (hash_id);
CREATE INDEX IF NOT EXISTS files_parent ON files (parent);
CREATE INDEX IF NOT EXISTS files_type_parent ON files (type, parent, path);
CREATE INDEX IF NOT EXISTS files_name ON files (name);
CREATE INDEX IF NOT EXISTS files_modified ON files (modified);
CREATE INDEX IF NOT EXISTS files_trash ON files (trash);
CREATE INDEX IF NOT EXISTS files_size ON files (size);

CREATE TABLE IF NOT EXISTS files_shares
-- Sharing links for files
(
	id INTEGER NOT NULL PRIMARY KEY,
	id_file INTEGER NOT NULL REFERENCES files(id) ON DELETE CASCADE,
	id_user INTEGER NULL REFERENCES users(id) ON DELETE CASCADE,
	created TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP CHECK (datetime(created) IS NOT NULL AND datetime(created) = created),
	hash_id TEXT NOT NULL,
	option INTEGER NOT NULL,
	expiry TEXT NULL CHECK (datetime(expiry) IS NULL OR datetime(expiry) = expiry),
	password TEXT NULL
);

CREATE UNIQUE INDEX IF NOT EXISTS files_shares_hash ON files_shares (hash_id);
CREATE INDEX IF NOT EXISTS files_shares_file ON files_shares (id_file);
CREATE INDEX IF NOT EXISTS files_shares_expiry ON files_shares (expiry);
