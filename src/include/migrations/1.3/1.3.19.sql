CREATE TABLE IF NOT EXISTS users_keys
-- Users encryption keys
(
	id INTEGER NOT NULL PRIMARY KEY,
	id_user INTEGER NOT NULL REFERENCES users (id) ON DELETE CASCADE,
	public TEXT NOT NULL,
	private TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS vaults
-- Encrypted data vaults
(
	id INTEGER NOT NULL PRIMARY KEY,
	expires TEXT NULL DEFAULT CURRENT_TIMESTAMP,
	data TEXT NOT NULL -- Encrypted data
);

CREATE TABLE IF NOT EXISTS vaults_keys
-- Encrypted vaults keys
(
	id INTEGER NOT NULL PRIMARY KEY,
	id_vault INTEGER NOT NULL REFERENCES vaults(id) ON DELETE CASCADE,
	id_keypair INTEGER NULL REFERENCES users_keys(id) ON DELETE CASCADE, -- NULL if the user is for example a script or API
	id_plugin INTEGER NULL REFERENCES plugins(id) ON DELETE CASCADE, -- Not NULL if the key is used by a plugin, then it should be deleted when the plugin is deleted
	key TEXT NOT NULL -- Encrypted vault secret key, specific to this user, as it is encrypted using their public key
);

-- Delete orphan vaults (zero linked keys)
CREATE TRIGGER IF NOT EXISTS vaults_keys_delete AFTER DELETE ON vaults_keys
BEGIN
	DELETE FROM vaults WHERE id = OLD.id_vault
		AND NOT EXISTS (SELECT id_vault FROM vaults_keys WHERE id_vault = OLD.id_vault);
END;
