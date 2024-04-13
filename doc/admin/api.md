# Introduction

### Débuter

Une API de type REST est disponible dans Paheko.

Pour accéder à l'API il faut un identifiant et un mot de passe, à créer dans le menu ==Configuration==, onglet ==Fonctions avancées==, puis ==API==.

L'API peut ensuite recevoir des requêtes REST sur l'URL `https://adresse_association/api{route}`.

Remplacer =={route}== par une des routes de l'API (voir ci-dessous).

La méthode HTTP (`GET`, `POST`, etc.) à utiliser est spécifiée pour chaque route.

Des exemples sont donnés pour l'utilisation de l'outil `curl` en ligne de commande, si vous souhaitez utiliser un autre langage de programmation il faudra adapter votre code.

### Formats des requêtes et réponses

Les paramètres peuvent être fournis sous les formes suivantes :

* dans les paramètres de l'URL (query string) : pour toutes les méthodes
* formulaire HTTP classique pour les requêtes `POST` :
  * `Content-Type: application/x-www-form-urlencoded`
  * ou `Content-Type: multipart/form-data`
* objet JSON pour les requêtes POST :
  * `Content-Type: application/json`

Les réponses sont renvoyées en JSON par défaut, sauf quand la route permet de choisir un autre format.

Les formats ODS et XLSX ne sont disponibles à l'import que si le serveur est configuré pour convertir ces formats.

De la même manière, le format XLSX n'est disponible que si le serveur est configuré pour générer ce format.

### Utiliser l'API

N'importe quel client HTTP capable de gérer TLS (HTTPS) et l'authentification basique fonctionnera.

En ligne de commande il est possible d'utiliser `curl`. Exemple pour télécharger la base de données :

```
curl -u test:secret https://[identifiant_association].paheko.cloud/api/download -o association.sqlite
```

On peut aussi utiliser `wget` en n'oubliant pas l'option `--auth-no-challenge` sinon l'authentification ne fonctionnera pas :

```
wget https://test:secret@[identifiant_association].paheko.cloud/api/download \
  --auth-no-challenge \
  -O association.sqlite
```

Exemple pour créer une écriture sous forme de formulaire :

```
curl -v -u test:secret \
  https://[identifiant_association].paheko.cloud/api/accounting/transaction \
  -F id_year=1 \
  -F label=Test \
  -F "date=01/02/2023"
  …
```

Ou sous forme d'objet JSON :

```
curl -v -u test:secret \
  https://[identifiant_association].paheko.cloud/api/accounting/transaction \
  -H 'Content-Type: application/json' \
  -d '{"id_year":1, "label": "Test écriture", "date": "01/02/2023", …}'
```

### Authentification

L'API utilise l'authentification [`Basic` de HTTP](https://fr.wikipedia.org/wiki/Authentification_HTTP#M%C3%A9thode_%C2%AB_Basic_%C2%BB).

### Erreurs

En cas d'erreur un code HTTP 4XX sera fourni, et le contenu sera un objet JSON avec une clé `error` contenant le message d'erreur.

# Routes

## Requêtes SQL

### POST sql.{FORMAT}

Exécute une requête SQL en lecture

| Paramètre | Type | Description |
| :- | :- | :- |
| `FORMAT` | `string` | Format de retour : `json`, `csv`, `ods` ou `xlsx` |
| `sql` | `string` | Requête SQL à exécuter. |

Si aucun format n'est passé (exemple : `…/api/sql`, sans point ni extension), `json` sera utilisé.

Permet d'exécuter une requête SQL `SELECT` (uniquement, pas de requête `UPDATE`, `DELETE`, `INSERT`, etc.) sur la base de données. La requête SQL doit être passée dans le corps de la requête HTTP, ou dans le paramètre `sql`.

S'il n'y a pas de limite à la requête, une limite à 1000 résultats sera ajoutée obligatoirement.

Exemple de requête :

```request
curl -u test:abcd https://paheko.monasso.tld/api/sql \
  -d 'SELECT nom, code_postal FROM users LIMIT 2;'
```

Exemple de réponse :

```response
{
    "count": 65,
    "results":
    [
        {
            "nom": "Ada Lovelace",
            "code_postal": null
        },
        {
            "nom": "James Coincoin",
            "code_postal": "78990"
        }
    ]
}
```

**Attention :** Les requêtes en écriture (`INSERT, DELETE, UPDATE, CREATE TABLE`, etc.) ne sont pas acceptées, il n'est pas possible de modifier la base de données directement via Paheko, afin d'éviter les soucis de données corrompues.

## Téléchargements

### GET download

Télécharger la base de données

Renvoie directement le fichier SQLite de la base de données.

Exemple de requête :

```request
curl -u test:abcd https://paheko.monasso.tld/api/download -o db.sqlite
```

### GET download/files

Télécharger un fichier ZIP contenant tous les fichiers

_(Depuis la version 1.3.4)_

Les fichiers inclus sont :

* documents
* fichiers liés aux écritures,
* fichiers liés des membres,
* fichiers joints aux pages du site web
* code des modules modifiés
* corbeille
* configuration : logo, icônes, etc.
* anciennes versions des fichiers

Exemple de requête :

```request
curl -u test:abcd https://paheko.monasso.tld/api/download/files -o backup_files.zip
```

## Site web

_(Depuis la version 1.4.0)_

### GET web

Liste de toutes les pages du site web

### GET web/{PAGE_URI}

Métadonnées de la page du site web

| Paramètre | Type | Description |
| :- | :- | :- |
| `PAGE_URI` | `string` | Adresse unique de la page. |
| `html` | `bool` | Si `true` ou `1`, une clé `html` sera ajoutée à la réponse avec le contenu de la page au format HTML. |

Exemple de réponse :

```response
[
    {
        "id": 13,
        "uri": "actualite",
        "title": "Actualit\u00e9",
        "path": null,
        "draft": 0,
        "published": "2019-04-22 18:00:00",
        "modified": "2023-09-12 15:44:55"
    },
    {
        "id": 66,
        "uri": "Affiches-des-bourses-aux-velos",
        "title": "Affiches des bourses aux v\u00e9los",
        "path": "Nos activit\u00e9s",
        "draft": 0,
        "published": "2019-07-18 19:05:00",
        "modified": "2023-04-04 14:44:04"
    },
    …
]
```

### PUT web/{PAGE_URI}

Modifie le contenu de la page

| Paramètre | Type | Description |
| :- | :- | :- |
| `PAGE_URI` | `string` | Adresse unique de la page. |

Exemple de requête :

```request
curl -u test:abcd https://paheko.monasso.tld/api/web/bourse-28-septembre -X PUT -d 'La bourse aura lieu le 28 septembre'
```

### POST web/{PAGE_URI}

Modifie les métadonnées de la page

| Paramètre | Type | Description |
| :- | :- | :- |
| `PAGE_URI` | `string` | Adresse unique de la page. |
| `id_parent` | `int|null` | Numéro de la catégorie parente de cette page. |
| `uri` | `string` | Nouvelle adresse unique de la page. |
| `title` | `string` | Titre de la page. |
| `type` | `int` | Type de page. `1` pour les catégories, `2` pour les pages simples. |
| `status` | `string` | Statut de la page. `online` si la page est en ligne, `draft` si la page est en brouillon. |
| `format` | `string` | Format de la page : `markdown`, `encrypted` ou `skriv` |
| `published` | `string` | Date et heure de publication au format `YYYY-MM-DD HH:mm:ss`. |
| `modified` | `string` | Date et heure de modification au format `YYYY-MM-DD HH:mm:ss`. |
| `content` | `string` | Contenu. |

Exemple de requête :

```request
curl -u test:abcd https://paheko.monasso.tld/api/web/bourse-28-septembre -F title="Bourse aux vélos du 28 septembre"
```

### DELETE web/{PAGE_URI}

Supprime la page et ses fichiers joints

| Paramètre | Type | Description |
| :- | :- | :- |
| `PAGE_URI` | `string` | Adresse unique de la page. |

Exemple de requête :

```request
curl -u test:abcd https://paheko.monasso.tld/api/web/bourse-28-septembre -X DELETE
```

### GET web/{PAGE_URI}.html

Contenu de la page web au format HTML

| Paramètre | Type | Description |
| :- | :- | :- |
| `PAGE_URI` | `string` | Adresse unique de la page. |

Exemple de requête :

```request
curl -u test:abcd https://paheko.monasso.tld/api/web/bourse-28-septembre.html
```

### GET web/{PAGE_URI}/children

Liste des pages et sous-catégories dans cette catégorie

| Paramètre | Type | Description |
| :- | :- | :- |
| `PAGE_URI` | `string` | Adresse unique de la page. |

Exemple de requête :

```request
curl -u test:abcd https://paheko.monasso.tld/api/web/actualite/children
```

Exemple de réponse :

```response
{
    "categories": [],
    "pages": [
        {
            "id": 86,
            "id_parent": 13,
            "uri": "bourse-aux-velos-le-30-septembre-et-1er-octobre",
            "title": "Bourse aux v\u00e9los 30 septembre et 1er octobre",
            "type": 2,
            "status": "online",
            "format": "skriv",
            "published": "2023-10-01 18:00:00",
            "modified": "2023-09-11 23:41:41",
            "content": "…"
        },
        …
    ]
}
```

### GET web/{PAGE_URI}/attachments

Liste des fichiers joints à la page

| Paramètre | Type | Description |
| :- | :- | :- |
| `PAGE_URI` | `string` | Adresse unique de la page. |

### GET web/{PAGE_URI}/{FILE_NAME}

Récupérer le fichier joint à la page

| Paramètre | Type | Description |
| :- | :- | :- |
| `PAGE_URI` | `string` | Adresse unique de la page. |
| `FILENAME` | `string` | Nom du fichier. |

### DELETE web/{PAGE_URI}/{FILE_NAME}

Supprime le fichier joint à la page

| Paramètre | Type | Description |
| :- | :- | :- |
| `PAGE_URI` | `string` | Adresse unique de la page. |
| `FILENAME` | `string` | Nom du fichier. |

## Membres

### GET user/categories

Liste des catégories de membres

_(Depuis la version 1.4.0)_

La liste est triée par nom, et inclue le nombre de membres de la catégorie dans la clé `count`.

Exemple de réponse :

```response
{
    "12": {
        "id": 12,
        "name": "Administration technique",
        "perm_web": 9,
        "perm_documents": 9,
        "perm_users": 9,
        "perm_accounting": 9,
        "perm_subscribe": 0,
        "perm_connect": 1,
        "perm_config": 9,
        "hidden": 0,
        "count": 1
    }
}
```

### GET user/category/{ID}.{FORMAT}

Exporte la liste des membres d'une catégorie

| Paramètre | Type | Description |
| :- | :- | :- |
| `ID` | `int` | Identifiant unique de la catégorie. |
| `FORMAT` | `string` | Format de sortie : `json`, `csv`, `ods` ou `xlsx` |

_(Depuis la version 1.4.0)_

### POST user/new

Créer un nouveau membre

| Paramètre | Type | Description |
| :- | :- | :- |
| `id_category` | `int` | Identifiant de la catégorie. Si absent, la catégorie par défaut sera utilisée. |
| `password` | `string` | Mot de passe du membre. |
| `force_duplicate` | `bool` | Si `true` ou `1`, alors aucune erreur ne sera renvoyée si le nom du membre correspond à un membre déjà existant. |

_(Depuis la version 1.4.0)_

Attention, cette méthode comporte des restrictions :

* il n'est pas possible de créer un membre dans une catégorie ayant accès à la configuration
* il n'est pas possible de définir l'OTP ou la clé PGP du membre créé
* seul un identifiant API ayant le droit "Administration" pourra créer des membres administrateurs

Il est possible d'utiliser tous les champs de la fiche membre en utilisant la clé unique du champ.

Sera renvoyée la liste des infos de la fiche membre.

Si un membre avec le même nom existe déjà (et que `force_duplicate` n'est pas utilisé), une erreur `409` sera renvoyée.

Exemple de requête :

```request
curl -F nom="Bla bla" -F id_category=3 -F password=abcdef123456 https://test:abcd@monpaheko.tld/api/user/new
```

### GET user/{ID}

Informations de la fiche d'un membre

| Paramètre | Type | Description |
| :- | :- | :- |
| `ID` | `int` | Identifiant unique du membre (différent du numéro). |

_(Depuis la version 1.4.0)_

Plusieurs clés supplémentaires sont retournées, en plus des champs de la fiche membre :

* `has_password`
* `has_pgp_key`
* `has_otp`

Exemple de réponse :

```response
{
    "has_password": true,
    "has_otp": false,
    "has_pgp_key": false,
    "id": 1,
    "id_category": 8,
    "date_login": "2021-06-06 09:17:39",
    "date_updated": null,
    "id_parent": null,
    "is_parent": false,
    "preferences": null,
    "numero": 1,
    "nom": "Ada Lovelace",
    "notes": null,
    "groupe_information": true,
    "groupe_benevoles": false,
    "email": "ada@lovelace.org",
    "telephone": "010101010101",
    "adresse": null,
    "code_postal": "21000",
    "ville": "DIJON",
    "pays": "FR",
    "date_inscription": "2012-02-25"
}
```

### DELETE user/{ID}

Supprime un membre

| Paramètre | Type | Description |
| :- | :- | :- |
| `ID` | `int` | Identifiant unique du membre (différent du numéro). |

_(Depuis la version 1.4.0)_

Seuls les identifiants d'API ayant le droit "Administration" pourront supprimer des membres.

Note : il n'est pas possible de supprimer via l'API un membre appartenant à une catégorie ayant accès à la configuration.

### POST user/{ID}

Modifie les infos de la fiche d'un membre

| Paramètre | Type | Description |
| :- | :- | :- |
| `ID` | `int` | Identifiant unique du membre (différent du numéro). |

_(Depuis la version 1.4.0)_

Notes :

* il n'est pas possible de modifier la catégorie d'un membre
* il n'est pas possible de modifier un membre appartenant à une catégorie ayant accès à la configuration.
* il n'est pas possible de modifier le mot de passe, l'OTP ou la clé PGP du membre créé
* il n'est pas possible de modifier des membres ayant accès à la configuration
* seul un identifiant d'API ayant l'accès en "Administration" pourra modifier un membre administrateur

### POST user/import

Importer un fichier de tableur de la liste des membres

Formats de fichiers acceptés : CSV, ODS, XLSX.

| Paramètre | Type | Description |
| :- | :- | :- |
| `mode` | `string` | Mode d'import du fichier. Voir ci-dessous pour les détails. _(Depuis la version 1.2.8)_ |
| `skip_lines` | `int` | Nombre de lignes à ignorer. Défaut : `1`. |
| `column` | `array` | Correspondance entre la colonne (clé, commence à zéro) et le champ de la fiche membre (valeur). |


Cette route nécessite une clé d'API ayant les droits d'administration, car importer un fichier peut permettre de modifier l'identifiant de connexion d'un administrateur et donc potentiellement d'obtenir l'accès à l'interface d'administration.

Le paramètre `mode` permet d'utiliser une de ces options pour spécifier le mode d'import :

* `auto` (défaut si le mode n'est pas spécifié) : met à jour la fiche d'un membre si son numéro existe, sinon crée un membre si le numéro de membre indiqué n'existe pas ou n'est pas renseigné
* `create` : ne fait que créer de nouvelles fiches de membre, si le numéro de membre existe déjà une erreur sera produite
* `update` : ne fait que mettre à jour les fiches de membre en utilisant le numéro de membre comme référence, si le numéro de membre n'existe pas une erreur sera produite

Exemple de requête :

```request
curl -u test:abcd https://monpaheko.tld/api/user/import \
  -F mode=create \
  -F 'column[0]=nom_prenom' \
  -F 'column[1]=code_postal' \
  -F skip_lines=0 \
  -F file=@membres.csv
```

Si aucun paramètre `column` n'est fourni, Paheko s'attend alors à ce que la première est ligne du tableau contienne le nom des colonnes, et que le nom des colonnes correspond au nom des champs de la fiche membre (ou à leur nom unique). Par exemple si votre fiche membre contient les champs *Nom et prénom* et *Adresse postale*, alors le fichier fourni devra ressembler à ceci :

| Nom et prénom | Adresse postale |
| :- | :- |
| Ada Lovelace | 42 rue du binaire, 21000 DIJON |

Ou à ceci :

| nom_prenom | adresse_postale |
| :- | :- |
| Ada Lovelace | 42 rue du binaire, 21000 DIJON |

La méthode renvoie un code HTTP `200 OK` si l'import s'est bien passé, sinon un code 400 et un message d'erreur JSON dans le corps de la réponse.

Utilisez la route `user/import/preview` avant pour vérifier que l'import correspond à ce que vous attendez.

Exemple pour modifier le nom du membre n°42 :

```
echo 'numero,nom' > membres.csv
echo '42,"Nouveau nom"' >> membres.csv
curl -u test:abcd https://monpaheko.tld/api/user/import -F file=@membres.csv
```

### PUT user/import

Importer un fichier de tableur de la liste des membres

Formats de fichiers acceptés : CSV, ODS, XLSX.

Identique à la même méthode en `POST`, mais les paramètres sont passés dans l'URL, et le fichier en contenu de la requête.

Exemple de requête :

```request
curl -u test:abcd https://monpaheko.tld/api/user/import?mode=create&column[0]=nom_prenom&skip_lines=0 \
  -T membres.csv
```

### POST user/import/preview

Prévisualise un import de membres, sans modifier les membres

Identique à `user/import`, mais l'import n'est pas enregistré. À la place l'API indique les modifications qui seraient apportées.

Renvoie un objet JSON comme ceci :

* `errors` : liste des erreurs d'import
* `created` : liste des membres ajoutés, chaque objet contenant tous les champs de la fiche membre qui serait créée
* `modified` : liste des membres modifiés, chaque membre aura une clé `id` et une clé `name`, ainsi qu'un objet `changed` contenant la liste des champs modifiés. Chaque champ modifié aura 2 propriétés `old` et `new`, contenant respectivement l'ancienne valeur du champ et la nouvelle.
* `unchanged` : liste des membres mentionnés dans l'import, mais qui ne seront pas affectés. Pour chaque membre une clé `name` et une clé `id` indiquant le nom et l'identifiant unique numérique du membre

Note : si `errors` n'est pas vide, alors il sera impossible d'importer le fichier avec `user/import`.

Exemple de requête :

```request
curl -u test:abcd https://monpaheko.tld/api/user/import/preview -F mode=update -F file=@/tmp/membres.csv
```

Exemple de réponse :

```response
{
    "created": [
        {
            "numero": 3434351,
            "nom": "Bla Bli Blu"
        }
    ],
    "modified": [
        {
            "id": 1,
            "name": "Ada Lovelace",
            "changed": {
                "nom": {
                    "old": "Ada Lvelavce",
                    "new": "Ada Lovelace"
                }
            }
        }
    ],
    "unchanged": [
        {
            "id": 2,
            "name": "Paul Muad'Dib"
        }
    ]
}
```

### PUT user/import/preview

Prévisualise un import de membres, sans modifier les membres

Idem quel la méthode en `POST` mais les paramètres doivent être passés dans l'URL, et le fichier dans le corps de la requête.

## Activités

### PUT services/subscriptions/import

Importer les inscriptions des membres aux activités

Fichiers acceptés : CSV, XLSX, ODS.

_(Depuis Paheko 1.3.2)_

Les activités et tarifs doivent déjà exister avant l'import.

Les colonnes suivantes peuvent être utilisées :

* Numéro de membre`**`
* Activité`**`
* Tarif
* Date d'inscription`**`
* Date d'expiration
* Montant à régler
* Payé ?

Les colonnes suivies de deux astérisques (`**`) sont obligatoires.

Exemple :

```
echo '"Numéro de membre","Activité","Tarif","Date d'inscription","Date d'expiration","Montant à régler","Payé ?"' > /tmp/inscriptions.csv
echo '42,"Cours de théâtre","Tarif adulte","01/09/2023","01/07/2023","123,50","Non"' >> /tmp/inscriptions.csv
curl -u test:abcd https://monpaheko.tld/api/services/subscriptions/import -T /tmp/inscriptions.csv
```

## Erreurs

Paheko dispose d'un système dédié à la gestion des erreurs internes, compatible avec les formats des logiciels AirBrake et errbit.

### POST errors/report

Ajouter un rapport d'erreur au log

Cette route permet d'ajouter une erreur au log de l'instance. Utile pour centraliser les erreurs de plusieurs instances.

Paheko utilise le format d'erreurs de [AirBrake](https://docs.airbrake.io/docs/devops-tools/api/#post-data-schema-v3) et errbit.

### GET errors/log

Log d'erreurs de l'instance

## Comptabilité

### GET accounting/years

Liste des exercices

Exemple de réponse :

```response
[
    {
        "id": 1,
        "label": "Premier exercice",
        "start_date": "2011-11-01",
        "end_date": "2013-01-31",
        "closed": 1,
        "id_chart": 1,
        "nb_transactions": 1194,
        "chart_name": "Plan comptable associatif 1999"
    },
    …
]
```

### GET accounting/charts

Liste des plans comptables

Exemple de réponse :

```response
[
    {
        "id": 2,
        "label": "Plan comptable associatif 2018",
        "country": "FR",
        "code": "PCA_2018",
        "archived": false
    }
]
```

### GET accounting/charts/{ID_CHART}/accounts

Liste des comptes pour le plan comptable indiqué

| Paramètre | Type | Description |
| :- | :- | :- |
| `ID_CHART` | `int` | ID du plan comptable. |

Exemple de réponse :

```response
[
    {
        "id": 312,
        "id_chart": 2,
        "code": "1",
        "label": "Classe 1 \u2014 Comptes de capitaux (Fonds propres, emprunts et dettes assimil\u00e9s)",
        "description": null,
        "position": 2,
        "type": 0,
        "user": false,
        "bookmark": false
    },
    …
]
```

### GET accounting/years/{ID_YEAR}/journal

Journal général des écritures de l'exercice indiqué

| Paramètre | Type | Description |
| :- | :- | :- |
| `ID_YEAR` | `int|string` | ID de l'exercice, ou `current`. |

Note : il est possible d'utiliser `current` comme paramètre pour `{ID_YEAR}` pour désigner l'exercice ouvert en cours. S'il y a plusieurs exercices ouverts, alors celui qui est le plus proche de la date actuelle sera utilisé.

### GET accounting/years/{ID_YEAR}/export/{TYPE}.{FORMAT}

Export d'un exercice

| Paramètre | Type | Description |
| :- | :- | :- |
| `ID_YEAR` | `int|string` | ID de l'exercice, ou `current`. |
| `TYPE` | `string` | Type d'export : `full`, `grouped`, `simple` ou `fec`. `simple` ne contient pas les écritures avancées. |
| `FORMAT` | `string` | Format d'export : `json`, `csv`, `ods` ou `xlsx` |

_(Depuis la version 1.4.0)_

### GET accounting/years/{ID_YEAR}/journal/{CODE}

Journal des écritures d'un compte

| Paramètre | Type | Description |
| :- | :- | :- |
| `ID_YEAR` | `int|string` | ID de l'exercice, ou `current`. |
| `CODE` | `int|string` | Code du compte. |

Exemple de réponse :

```response
[
    {
        "id": 9297,
        "id_line": 22401,
        "date": "2022-02-08",
        "debit": 0,
        "credit": 850,
        "change": 850,
        "sum": 850,
        "reference": "POS-SESSION-434",
        "type": 0,
        "label": "Session de caisse n\u00b0434",
        "line_label": null,
        "line_reference": null,
        "id_project": null,
        "project_code": null,
        "files": 1,
        "status": 0
    },
    …
]
```

### GET accounting/years/{ID_YEAR}/journal/={ID}

Journal des écritures d'un compte

| Paramètre | Type | Description |
| :- | :- | :- |
| `ID_YEAR` | `int|string` | ID de l'exercice, ou `current`. |
| `ID` | `int` | ID du compte. |

### POST accounting/transaction

Créer une nouvelle écriture

| Paramètre | Type | Description |
| :- | :- | :- |
| `id_year` | `int` | Identifiant de l'exercice. |
| `date` | `string` | Date au format `YYYY-MM-DD` ou `DD/MM/YYYY` |
| `type` | `string` | Type d'écriture. |
| `reference` | `string|null` | Numéro de pièce comptable |
| `notes` | `string|null` | Remarques (texte multi ligne) |
| `linked_transactions` | `array(int, …)|null` | Tableau des IDs des écritures à lier à l'écriture *(depuis 1.3.5)*
| `linked_users` | `array(int, …)|null` | Tableau des IDs des membres à lier à l'écriture *(depuis 1.3.3)* |
| `linked_subscriptions` | `array(int, …)|null` | Tableau des IDs des inscriptions à lier à l'écriture *(depuis 1.4.0)* |

#### Types d'écriture

| Type | Description |
| :- | :- |
| `expense` | Dépense |
| `revenue` | Recette |
| `transfer` | Virement |
| `debt` | Dette |
| `credit` | Créance |
| `advanced` | Saisie avancée |

Les écritures avancées (multi-lignes) ont obligatoirement le type `advanced`. Les autres écritures sont appelées *"écritures simplifiées"* et ne peuvent comporter que deux lignes.

#### Paramètres des écritures simplifiées

| Paramètre | Type | Description |
| :- | :- | :- |
| `amount` | `string` | Montant de l'écriture, au format flottant (`53,34`) |
| `credit` | `string` | Code du compte porté au crédit |
| `debit` | `string` | Code du compte porté au débit |
| `id_project` | `int|null` | ID du projet à affecter |
| `payment_reference` | `int|null` | référence de paiement |

#### Paramètres des écritures avancées

| Paramètre | Type | Description |
| :- | :- | :- |
| `lines` | `array(object, …)` | un tableau dont chaque élément est un objet correspondant à une ligne de l'écriture. |

Structure de l'objet représentant une ligne de l'écriture :

| Paramètre | Type | Description |
| :- | :- | :- |
| `account` | `string` | Code du compte |
| `id_account` | `int` | Identifiant du compte (ID). Peut être utilisé en alternative au code du compte. |
| `credit` | `string` | Montant à inscrire au crédit (doit être zéro ou non renseigné si `debit` est renseigné, et vice-versa) |
| `debit` | `string` | montant à inscrire au débit |
| `label` | `string|null` | libellé de la ligne |
| `reference` | `string|null` | référence de la ligne (aussi appelé référence du paiement pour les écritures simplifiées) |
| `id_project` | `int|null` | ID du projet à affecter à cette ligne |

Exemple de requête :

```request
curl -F 'id_year=12' \
  -F 'label=Test' \
  -F 'date=01/02/2022' \
  -F 'type=expense' \
  -F 'amount=42,45' \
  -F 'debit=512A' \
  -F 'credit=601'
```

### GET accounting/transaction/{ID_TRANSACTION}

Détails de l'écriture

| Paramètre | Type | Description |
| :- | :- | :- |
| `ID_TRANSACTION` | `int` | ID de l'écriture. |

Exemple de réponse :

```response
{
  "id": 9302,
  "type": 0,
  "status": 0,
  "label": "Session de caisse n\u00b0439",
  "notes": null,
  "reference": "POS-SESSION-439",
  "date": "2022-02-12",
  "hash": null,
  "prev_id": null,
  "prev_hash": null,
  "id_year": 12,
  "id_creator": 5883,
  "url": "http:\/\/dev.paheko.localhost\/admin\/acc\/transactions\/details.php?id=9302",
  "lines": [
    {
      "id": 22421,
      "id_transaction": 9302,
      "id_account": 542,
      "credit": 0,
      "debit": 3000,
      "reference": "CE342",
      "label": null,
      "reconciled": false,
      "id_project": null,
      "account_code": "5112",
      "account_label": "Ch\u00e8ques \u00e0 encaisser",
      "account_position": 3,
      "project_name": null,
      "account_selector": {
        "542": "5112 \u2014 Ch\u00e8ques \u00e0 encaisser"
      }
    },
    …
  ]
}
```

### POST accounting/transaction/{ID_TRANSACTION}

Modifier l'écriture

| Paramètre | Type | Description |
| :- | :- | :- |
| `ID_TRANSACTION` | `int` | ID de l'écriture. |

Si l'écriture est verrouillée, ou dans un exercice clôturé, la modification sera impossible.

Voir la route `POST accounting/transaction` (création d'une écriture) pour la liste des paramètres.

### GET accounting/transaction/{ID_TRANSACTION}/users

Liste des membres liés à une écriture

| Paramètre | Type | Description |
| :- | :- | :- |
| `ID_TRANSACTION` | `int` | ID de l'écriture. |

### POST accounting/transaction/{ID_TRANSACTION}/users

Met à jour la liste des membres liés à une écriture

| Paramètre | Type | Description |
| :- | :- | :- |
| `ID_TRANSACTION` | `int` | ID de l'écriture. |
| `users` | `array(int, …)` | ID des membres. |

Exemple de requête :

```
 curl -v "https://…/api/accounting/transaction/9337/users"  -F 'users[]=2'
```

### DELETE accounting/transaction/{ID_TRANSACTION}/users

Efface la liste des membres liés à une écriture

| Paramètre | Type | Description |
| :- | :- | :- |
| `ID_TRANSACTION` | `int` | ID de l'écriture. |

### GET accounting/transaction/{ID_TRANSACTION}/subscriptions

Liste des inscriptions (aux activités) liées à une écriture

| Paramètre | Type | Description |
| :- | :- | :- |
| `ID_TRANSACTION` | `int` | ID de l'écriture. |

_(Depuis la version 1.3.6)_

### POST accounting/transaction/{ID_TRANSACTION}/subscriptions

Met à jour la liste des inscriptions liées à une écriture

| Paramètre | Type | Description |
| :- | :- | :- |
| `ID_TRANSACTION` | `int` | ID de l'écriture. |
| `subscriptions` | `array(int, …)` | ID des inscriptions. |

_(Depuis la version 1.3.6)_

Exemple de requête :

```
 curl -v "https://…/api/accounting/transaction/9337/subscriptions"  -F 'subscriptions[]=2'
```

### DELETE accounting/transaction/{ID_TRANSACTION}/subscriptions

Efface la liste des inscriptions liées à une écriture

| Paramètre | Type | Description |
| :- | :- | :- |
| `ID_TRANSACTION` | `int` | ID de l'écriture. |

_(Depuis la version 1.3.6)_

### GET accounting/transaction/{ID_TRANSACTION}/transactions

Liste les écritures liées à une écriture.

| Paramètre | Type | Description |
| :- | :- | :- |
| `ID_TRANSACTION` | `int` | ID de l'écriture. |

_(Depuis la version 1.3.7)_

### POST accounting/transaction/{ID_TRANSACTION}/transactions

Met à jour la liste des écritures liées à une écriture, en utilisant les ID des écritures, passées dans un tableau nommé `transactions`.

| Paramètre | Type | Description |
| :- | :- | :- |
| `ID_TRANSACTION` | `int` | ID de l'écriture. |
| `transactions` | `array(int, …)` | ID des inscriptions. |

_(Depuis la version 1.3.7)_

Exemple de requête :

```
 curl -v "http://…/api/accounting/transaction/9337/transactions"  -F 'transactions[]=2'
```

### DELETE accounting/transaction/{ID_TRANSACTION}/transactions

Efface la liste des écritures liées à une écriture.

| Paramètre | Type | Description |
| :- | :- | :- |
| `ID_TRANSACTION` | `int` | ID de l'écriture. |

_(Depuis la version 1.3.7)_
