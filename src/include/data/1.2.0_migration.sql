CREATE TABLE IF NOT EXISTS documents_data
-- Data stored by user templates
(
    id INTEGER NOT NULL PRIMARY KEY,
    document TEXT NOT NULL,
    key TEXT NULL,
    value TEXT NOT NULL
);

CREATE UNIQUE INDEX documents_data_key ON documents_data (document, key);
