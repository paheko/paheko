ALTER TABLE users ADD COLUMN otp_recovery_codes TEXT NULL;
ALTER TABLE users_categories ADD COLUMN allow_passwordless_login INTEGER NOT NULL DEFAULT 0;
ALTER TABLE users_categories ADD COLUMN force_otp INTEGER NOT NULL DEFAULT 0;
ALTER TABLE users_categories ADD COLUMN force_pgp INTEGER NOT NULL DEFAULT 0;
