#!/bin/bash

# Ce script permet de convertir les anciennes catégories en projets
# et d'affecter ces projets aux écritures.
#
# - Création de nouveaux comptes de projets
# - Affectation des lignes des écritures à ces nouvelles écritures
#
# Le premier argument doit être l'ancienne base de données (version 0.9.8)
# Le second argument doit être la nouvelle base de données (1.0)
#
# Évidemment ça ne marche que si la BDD 1.0 est une mise à jour de la BDD de la 0.9.8 !
# Sinon ça sera tout mélangé !

if [ ! -f "$1" ] || [ ! -f "$2" ]; then
	echo "Usage: $0 OLD_DATABASE NEW_DATABASE"
	exit 1
fi

sqlite3 "$1" <<EOF
	CREATE TEMP TABLE projects_categories (id, code, label, description);

	INSERT INTO projects_categories SELECT id, NULL, intitule, description FROM compta_categories;

	UPDATE projects_categories SET code = printf('99%03d', rowid);

	--SELECT code, label FROM projects_categories;
	--SELECT id, (SELECT code FROM projects_categories WHERE id = id_categorie) FROM compta_journal WHERE id_categorie IS NOT NULL;

	CREATE TEMP TABLE projects_transactions (id, code, account_id);

	INSERT INTO projects_transactions
		SELECT
			id,
			(SELECT code FROM projects_categories WHERE id = id_categorie),
			NULL
		FROM compta_journal
		WHERE id_categorie IS NOT NULL;

	ATTACH '${2}' AS new;

	BEGIN;

	INSERT INTO new.acc_accounts (id_chart, code, label, description, position, type, user)
		SELECT
			(SELECT id FROM acc_charts WHERE code = 'PCGA1999'),
			code,
			label,
			description,
			0,
			7, -- type
			1
		FROM projects_categories;

	UPDATE projects_transactions AS t SET account_id = (SELECT id FROM new.acc_accounts a WHERE a.code = t.code);

	UPDATE new.acc_transactions_lines AS l
		SET
			id_analytical = (SELECT account_id FROM projects_transactions t WHERE t.id = l.id_transaction);

	COMMIT;
EOF