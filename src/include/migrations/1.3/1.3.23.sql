CREATE TABLE IF NOT EXISTS users_app_passwords
(
	id INTEGER NOT NULL PRIMARY KEY,
	id_user INTEGER NOT NULL REFERENCES users (id) ON DELETE CASCADE,
	id_plugin INTEGER NULL REFERENCES plugins (id) ON DELETE CASCADE,
	name TEXT NOT NULL,
	password TEXT NOT NULL,
	last_seen DATETIME NULL CHECK (last_seen IS NULL OR datetime(last_seen) = last_seen)
);
