CREATE TABLE IF NOT EXISTS users_app_passwords
(
	id INTEGER NOT NULL PRIMARY KEY,
	id_user INTEGER NULL REFERENCES users (id) ON DELETE CASCADE,
	name TEXT NOT NULL,
	password TEXT NOT NULL,
	last_seen DATETIME NOT NULL CHECK (datetime(last_seen) = last_seen)
);
