[toc]

Paheko permet d'effectuer des requêtes SQL en lecture dans la base de données, que ça soit via son API, ou via les recherches avancées.

Dans ce cas, certaines fonctionnalités additionnelles sont offertes par Paheko. Ces fonctionnalités ne sont bien sûr pas disponibles si la requête est effectuée avec un autre logiciel directement sur la base de données.

# Collations

## Collation U_NOCASE

Cet algorithme de classement (*collation* en anglais), permet de trier des chaînes de texte UTF-8 sans prendre en compte les accents ni les différences de majuscules et minuscules.

Il peut être utilisé dans les clauses `ORDER BY` ou de comparaison.

Par exemple si on a une table `users` qui contient une colonne `nom` et les enregistrements suivants en faisant un `SELECT nom FROM users` :

```
Émilien
Emilie
Émilia
Emma
```

Alors la requête `SELECT nom FROM users ORDER BY nom COLLATE U_NOCASE` donnera l'ordre suivant :

```
Émilia
Emilie
Émilien
Emma
```

Note : pour des raisons de performances, cette comparaison n'est effectuée que sur les 100 premiers caractères de la chaîne de texte.

# Fonctions

## Fonction transliterate_to_ascii

Syntaxe : `transliterate_to_ascii(string value)`

Cette fonction permet de transformer une chaîne de texte UTF-8 en ASCII, sans accents, et en minuscules.

```
SELECT transliterate_to_ascii('Ça boume les jeunôts ?');
-> ca boume les jeunots ?
```

## Fonction email_hash

Syntaxe : `email_hash(string email)`

Renvoie le hash d'une adresse e-mail normalisée, utile pour faire des jointures avec la table emails qui stocke le statut anonyme d'une adresse e-mail, conformément au RGPD.

```
SELECT * FROM users u
INNER JOIN emails e ON 
  u.email IS NOT NULL
  AND e.hash = email_hash(u.email)
```

## Fonction print_dynamic_field

Syntaxe : `print_dynamic_field(string field_name, mixed value)`

Affiche la valeur du champ de la fiche membre.

Surtout utile pour afficher les champs de fiche membre de type "choix multiple".

* Le premier paramètre doit être le nom du champ entre guillemets,
* le second paramètre étant la valeur du champ (donc le nom de la colonne généralement)

```
SELECT print_dynamic_field('moyen_paiement', u.moyen_paiement)
  FROM users AS u
  WHERE u.moyen_paiement IS NOT NULL;
```

## Fonction match_dynamic_field

Syntaxe : `match_dynamic_field(string field_name, mixed value, mixed search[, mixed search...])`

Renvoie `1` si la condition de recherche passée en 3ème paramètre et suivants correspond à la valeur passée en second paramètre.

Surtout utile pour savoir si un champ de fiche membre à choix multiple correspond à une recherche.

Il est possible de passer la chaîne `AND` ou `OR` en 3ème paramètre pour spécifier si la recherche doit vérifier la présence de tous les éléments ou seulement un des éléments. Si aucune chaîne n'est passée, c'est la condition `OR` qui sera utilisée.

Exemple si on veut lister les membres inscrits au groupe de travail "Communication" :

```
SELECT nom
  FROM users AS u
  WHERE match_dynamic_field('groupe_travail', u.groupe_travail, 'Communication');
```

Exemple si on veut lister les membres inscrits soit au groupe de travail "Communication", soit au groupe de travail "Accueil" :

```
SELECT nom
  FROM users AS u
  WHERE match_dynamic_field('groupe_travail', u.groupe_travail, 'Communication', 'Accueil');
```

Exemple si on veut lister les membres inscrits dans les deux groupes de travail "Communication" et "Accueil" :

```
SELECT nom
  FROM users AS u
  WHERE match_dynamic_field('groupe_travail', u.groupe_travail, 'AND', Communication', 'Accueil');
```

