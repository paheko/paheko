ALTER TABLE searches RENAME COLUMN created TO updated;
ALTER TABLE searches ADD COLUMN description TEXT NULL;

INSERT INTO config (key, value) VALUES ('analytical_mandatory', 0);
