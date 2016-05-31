-- Colonne manquante
ALTER TABLE rappels_envoyes ADD COLUMN id_rappel INTEGER NULL REFERENCES rappels (id);

-- Un bug a permis d'insérer des comptes avec des lettres minuscules, créant des problèmes
-- corrigeons donc les comptes pour les mettre en majuscules.

UPDATE compta_comptes SET id = UPPER(id);

-- Le champ id_auteur était à NOT NULL, il faut corriger ça pour pouvoir avoir un rapprochement anonyme
-- une fois que le membre a été supprimé

CREATE TABLE compta_rapprochement2
-- Rapprochement entre compta et relevés de comptes
(
    id_operation INTEGER NOT NULL PRIMARY KEY REFERENCES compta_journal (id),
    date TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    id_auteur INTEGER NULL REFERENCES membres (id)
);

INSERT INTO compta_rapprochement2 SELECT operation, date, auteur FROM compta_rapprochement;

DROP TABLE compta_rapprochement;

ALTER TABLE compta_rapprochement2 RENAME TO compta_rapprochement;