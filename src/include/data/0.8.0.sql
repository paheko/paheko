-- Ajouter champ pour OTP
ALTER TABLE membres ADD COLUMN secret_otp TEXT NULL;

-- Ajouter champ clé PGP
ALTER TABLE membres ADD COLUMN clef_pgp TEXT NULL;

-- Sessions
CREATE TABLE membres_sessions
(
	selecteur TEXT NOT NULL,
	token TEXT NOT NULL,
	id_membre INTEGER NOT NULL,
	expire TEXT NOT NULL,

	FOREIGN KEY (id_membre) REFERENCES membres (id),
	PRIMARY KEY (selecteur, id_membre)
);