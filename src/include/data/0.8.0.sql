-- Ajouter champ pour OTP
ALTER TABLE membres ADD COLUMN secret_otp TEXT NULL;

-- Ajouter champ clé PGP
ALTER TABLE membres ADD COLUMN clef_pgp TEXT NULL;