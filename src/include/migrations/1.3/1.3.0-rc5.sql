ALTER TABLE web_pages RENAME TO web_pages_old;

.read schema.sql

-- Drop foreign key constant between web_pages and files, as files can just be a cache,
-- with missing web pages directories
INSERT INTO web_pages SELECT * FROM web_pages_old;
DROP TABLE web_pages_old;
