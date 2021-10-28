ALTER TABLE services_reminders_sent RENAME TO srs_old;

-- Add new column in services_reminders_sent
CREATE TABLE IF NOT EXISTS services_reminders_sent
-- Enregistrement des rappels envoyés à qui et quand
(
    id INTEGER NOT NULL PRIMARY KEY,

    id_user INTEGER NOT NULL REFERENCES membres (id) ON DELETE CASCADE,
    id_service INTEGER NOT NULL REFERENCES services (id) ON DELETE CASCADE,
    id_reminder INTEGER NOT NULL REFERENCES services_reminders (id) ON DELETE CASCADE,

    sent_date TEXT NOT NULL DEFAULT CURRENT_DATE CHECK (date(sent_date) IS NOT NULL AND date(sent_date) = sent_date),
    due_date TEXT NOT NULL CHECK (date(due_date) IS NOT NULL AND date(due_date) = due_date)
);

CREATE UNIQUE INDEX IF NOT EXISTS srs_index ON services_reminders_sent (id_user, id_service, id_reminder, due_date);

CREATE INDEX IF NOT EXISTS srs_reminder ON services_reminders_sent (id_reminder);
CREATE INDEX IF NOT EXISTS srs_user ON services_reminders_sent (id_user);

INSERT INTO services_reminders_sent SELECT id, id_user, id_service, id_reminder, date, date FROM srs_old;
DROP TABLE srs_old;

-- Missing acc_years_delete trigger, again, because of missing symlink in previous release
-- Make sure id_account is reset when a year is deleted
CREATE TRIGGER IF NOT EXISTS acc_years_delete BEFORE DELETE ON acc_years BEGIN
    UPDATE services_fees SET id_account = NULL, id_year = NULL WHERE id_year = OLD.id;
END;

