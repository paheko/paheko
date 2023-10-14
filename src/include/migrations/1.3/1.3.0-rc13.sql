ALTER TABLE config_users_fields RENAME TO config_users_fields_old;
DROP INDEX config_users_fields_name;

CREATE TABLE IF NOT EXISTS config_users_fields (
	id INTEGER NOT NULL PRIMARY KEY,
	name TEXT NOT NULL,
	sort_order INTEGER NOT NULL,
	type TEXT NOT NULL,
	label TEXT NOT NULL,
	help TEXT NULL,
	required INTEGER NOT NULL DEFAULT 0,
	user_access_level INTEGER NOT NULL DEFAULT 0,
	management_access_level INTEGER NOT NULL DEFAULT 1,
	list_table INTEGER NOT NULL DEFAULT 0,
	options TEXT NULL,
	default_value TEXT NULL,
	sql TEXT NULL,
	system TEXT NULL
);

CREATE UNIQUE INDEX IF NOT EXISTS config_users_fields_name ON config_users_fields (name);

INSERT INTO config_users_fields
	SELECT id, name, sort_order, type, label, help, required,
		CASE WHEN write_access = 1 THEN 2
			WHEN read_access = 1 THEN 1
			ELSE 0 END,
		1,
		list_table,
		options,
		default_value,
		sql,
		system
	FROM config_users_fields_old;
