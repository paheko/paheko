ALTER TABLE fichiers_membres RENAME TO fichiers_membres_old;
ALTER TABLE fichiers_wiki_pages RENAME TO fichiers_wiki_pages_old;
ALTER TABLE fichiers_acc_transactions RENAME TO fichiers_acc_transactions_old;

.read 1.0.0_schema.sql

INSERT INTO fichiers_membres SELECT * FROM fichiers_membres_old;
INSERT INTO fichiers_wiki_pages SELECT * FROM fichiers_wiki_pages_old;
INSERT INTO fichiers_acc_transactions SELECT * FROM fichiers_acc_transactions_old;

DROP TABLE fichiers_membres_old;
DROP TABLE fichiers_wiki_pages_old;
DROP TABLE fichiers_acc_transactions_old;