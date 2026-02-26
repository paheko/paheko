ALTER TABLE searches RENAME COLUMN created TO updated;
ALTER TABLE searches ADD COLUMN description TEXT NULL;

INSERT INTO config (key, value) VALUES ('analytical_mandatory', 0);

-- Put 580 account into "internal"
UPDATE acc_accounts SET type = 16 WHERE code = '580';
