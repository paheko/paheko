ALTER TABLE users ADD COLUMN otp_recovery_codes TEXT NULL;
ALTER TABLE users_categories ADD COLUMN allow_passwordless_login INTEGER NOT NULL DEFAULT 0;
ALTER TABLE users_categories ADD COLUMN force_otp INTEGER NOT NULL DEFAULT 0;
ALTER TABLE users_categories ADD COLUMN force_pgp INTEGER NOT NULL DEFAULT 0;

ALTER TABLE web_pages ADD COLUMN inherited_status INTEGER NOT NULL DEFAULT 0;
UPDATE web_pages SET status = 0 WHERE status = 'draft';
UPDATE web_pages SET status = 1 WHERE status = 'private';
UPDATE web_pages SET status = 2 WHERE status = 'online';

WITH RECURSIVE children(status, inherited_status, id_parent, id, level, new_status) AS (
	SELECT status, inherited_status, id_parent, id, 1, status FROM web_pages WHERE id_parent IS NULL
	UNION ALL
	SELECT p.status, p.inherited_status, p.id_parent, p.id, level + 1, CASE WHEN p.status < children.new_status THEN p.status ELSE children.new_status END
	FROM web_pages p
		JOIN children ON children.id = p.id_parent
	LIMIT 100000
)
UPDATE web_pages SET inherited_status = IFNULL((SELECT new_status FROM children WHERE id = web_pages.id), status);

DROP INDEX IF EXISTS acc_years_closed;
ALTER TABLE acc_years RENAME COLUMN closed TO status;

CREATE INDEX IF NOT EXISTS acc_years_status ON acc_years (status);

-- Fix typo in account name
UPDATE acc_accounts SET label = 'Malus sur emballages' WHERE label = 'Malis sur emballages';
