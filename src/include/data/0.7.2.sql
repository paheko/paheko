# Colonne manquante
ALTER TABLE rappels_envoyes ADD COLUMN id_rappel INTEGER NULL REFERENCES rappels (id);

# Un bug a permis d'insérer des comptes avec des lettres minuscules, créant des problèmes
# corrigeons donc les comptes pour les mettre en majuscules.
UPDATE compta_comptes SET id = UPPER(id);