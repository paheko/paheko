-- Mise à jour des URI du wiki pour ne pas inclure les tirets en début et fin de chaîne
-- (problème de concordance entre API PHP et données SQLite)
UPDATE wiki_pages SET uri = trim(uri, '-') WHERE uri != trim(uri, '-');
