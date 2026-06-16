-- Add archived column
ALTER TABLE acc_accounts ADD COLUMN archived INTEGER NOT NULL DEFAULT 0;
