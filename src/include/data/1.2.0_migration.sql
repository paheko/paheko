CREATE TABLE IF NOT EXISTS documents_data
-- Data stored by user templates
(
    id INTEGER NOT NULL PRIMARY KEY,
    document TEXT NOT NULL,
    key TEXT NULL,
    value TEXT NOT NULL
);

CREATE UNIQUE INDEX documents_data_key ON documents_data (document, key);

-- Balance des comptes par exercice
CREATE VIEW IF NOT EXISTS acc_accounts_sums
AS
    SELECT t.id_year, a.id, a.label, a.code, a.position,
        SUM(l.credit) AS credit,
        SUM(l.debit) AS debit,
        CASE WHEN a.position IN (4, 1) THEN SUM (l.debit - l.credit) ELSE SUM(l.credit - l.debit)  END AS balance
    FROM acc_accounts a
    LEFT JOIN acc_transactions_lines l ON l.id_account = a.id
    LEFT JOIN acc_transactions t ON t.id = l.id_transaction
    GROUP BY t.id_year, a.id;