Une API de type REST est disponible dans Paheko.

Pour accéder à l'API il faut un identifiant et un mot de passe, à créer dans le menu ==Configuration==, onglet ==Fonctions avancées==, puis ==API==.

L'API peut ensuite recevoir des requêtes REST sur l'URL `https://adresse_association/api/{chemin}/`.

Remplacer =={chemin}== par un des chemins de l'API (voir ci-dessous). La méthode HTTP à utiliser est spécifiée pour chaque chemin.

Pour les requêtes de type `POST`, les paramètres peuvent être envoyés par le client sous forme de formulaire HTTP classique (`application/x-www-form-urlencoded`) ou sous forme d'objet JSON. Dans ce cas le `Content-Type` doit être positionné sur `application/json`.

Les réponses sont faites en JSON par défaut.

<<toc level=3>>

# Utiliser l'API

N'importe quel client HTTP capable de gérer TLS (HTTPS) et l'authentification basique fonctionnera.

En ligne de commande il est possible d'utiliser `curl`. Exemple pour télécharger la base de données :

```
curl https://test:coucou@[identifiant_association].paheko.cloud/api/download -o association.sqlite
```

On peut aussi utiliser `wget` en n'oubliant pas l'option `--auth-no-challenge` sinon l'authentification ne fonctionnera pas :

```
wget https://test:coucou@[identifiant_association].paheko.cloud/api/download  --auth-no-challenge -O association.sqlite
```

Exemple pour créer une écriture sous forme de formulaire :

```
curl -v "http://test:test@[identifiant_association].paheko.cloud/api/accounting/transaction" -F id_year=1 -F label=Test -F "date=01/02/2023" …
```

Ou sous forme d'objet JSON :

```
curl -v "http://test:test@[identifiant_association].paheko.cloud/api/accounting/transaction" -H 'Content-Type: application/json' -d '{"id_year":1, "label": "Test écriture", "date": "01/02/2023"}'
```


# Authentification

Il ne faut pas oublier de fournir le nom d'utilisateur et mot de passe en HTTP :

```
curl http://test:abcd@paheko.monasso.tld/api/download/
```

# Erreurs

En cas d'erreur un code HTTP 4XX sera fourni, et le contenu sera un objet JSON avec une clé `error` contenant le message d'erreur.

# Chemins

## Requêtes SQL

### POST sql.{FORMAT}

| Paramètre | Type | Description |
| :- | :- | :- |
| `FORMAT` | `string:enum(json, csv, ods, xlsx)` | Format de retour. |
| `sql` | `string` | Requête SQL à exécuter. |

Si aucun format n'est passé (exemple : `…/api/sql`, sans point ni extension), `json` sera utilisé.

Permet d'exécuter une requête SQL `SELECT` (uniquement, pas de requête `UPDATE`, `DELETE`, `INSERT`, etc.) sur la base de données. La requête SQL doit être passée dans le corps de la requête HTTP, ou dans le paramètre `sql`.

S'il n'y a pas de limite à la requête, une limite à 1000 résultats sera ajoutée obligatoirement.

```request
curl https://test:abcd@paheko.monasso.tld/api/sql -d 'SELECT nom, code_postal FROM users LIMIT 2;'
```

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

Télécharger la base de données complète. Renvoie directement le fichier SQLite de la base de données.

Exemple :

```request
curl https://test:abcd@paheko.monasso.tld/api/download -o db.sqlite
```

### GET download/files

_(Depuis la version 1.3.4)_

Télécharger un fichier ZIP contenant tous les fichiers (documents, fichiers des écritures, des membres, modules modifiés, etc.).

Exemple :

```request
curl https://test:abcd@paheko.monasso.tld/api/download/files -o backup_files.zip
```

## Site web

_(Depuis la version 1.4.0)_

### GET web

Renvoie la liste de toutes les pages du site web.

### GET web/{PAGE_URI}

Renvoie un objet JSON avec toutes les infos de la page donnée.

| Paramètre | Type | Description |
| :- | :- | :- |
| `PAGE_URI` | `string` | Adresse unique de la page. |
| `html` | `bool` | Si `true` ou `1`, une clé `html` sera ajoutée à la réponse avec le contenu de la page au format HTML. |

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

Modifie le contenu de la page.

| Paramètre | Type | Description |
| :- | :- | :- |
| `PAGE_URI` | `string` | Adresse unique de la page. |

```request
curl https://test:abcd@paheko.monasso.tld/api/web/bourse-28-septembre -X PUT -d 'La bourse aura lieu le 28 septembre'
```

### POST web/{PAGE_URI}

Modifie les métadonnées de la page.

| Paramètre | Type | Description |
| :- | :- | :- |
| `PAGE_URI` | `string` | Adresse unique de la page. |
| `id_parent` | `int|null` | Numéro de la catégorie parente de cette page. |
| `uri` | `string` | Nouvelle adresse unique de la page. |
| `title` | `string` | Titre de la page. |
| `type` | `int:enum(1, 2)` | Type de page. `1` pour les catégories, `2` pour les pages simples. |
| `status` | `string:enum(online, draft)` | Statut de la page. `online` si la page est en ligne, `draft` si la page est en brouillon. |
| `format` | `string:enum(markdown, encrypted, skriv)` | Format de la page. |
| `published` | `string:datetime` | Date et heure de publication. |
| `modified` | `string:datetime` | Date et heure de modification. |
| `content` | `string` | Contenu. |

```request
curl https://test:abcd@paheko.monasso.tld/api/web/bourse-28-septembre -F title="Bourse aux vélos du 28 septembre"
```

### DELETE web/{PAGE_URI}

Supprime la page et ses fichiers joints.

| Paramètre | Type | Description |
| :- | :- | :- |
| `PAGE_URI` | `string` | Adresse unique de la page. |

```request
curl https://test:abcd@paheko.monasso.tld/api/web/bourse-28-septembre -X DELETE
```

### GET web/{PAGE_URI}.html

Renvoie uniquement le contenu de la page au format HTML.

| Paramètre | Type | Description |
| :- | :- | :- |
| `PAGE_URI` | `string` | Adresse unique de la page. |

```request
curl https://test:abcd@paheko.monasso.tld/api/web/bourse-28-septembre.html
```

### GET web/{PAGE_URI}/children

Renvoie la liste des pages et sous-catégories dans cette catégorie.

| Paramètre | Type | Description |
| :- | :- | :- |
| `PAGE_URI` | `string` | Adresse unique de la page. |

```request
curl https://test:abcd@paheko.monasso.tld/api/web/actualite/children
```

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

Renvoie la liste des fichiers joints à la page.

| Paramètre | Type | Description |
| :- | :- | :- |
| `PAGE_URI` | `string` | Adresse unique de la page. |

### GET web/{PAGE_URI}/{FILE_NAME}

Renvoie le fichier joint à la page.

| Paramètre | Type | Description |
| :- | :- | :- |
| `PAGE_URI` | `string` | Adresse unique de la page. |
| `FILENAME` | `string` | Nom du fichier. |

### DELETE web/{PAGE_URI}/{FILE_NAME}

Supprime le fichier joint à la page.

| Paramètre | Type | Description |
| :- | :- | :- |
| `PAGE_URI` | `string` | Adresse unique de la page. |
| `FILENAME` | `string` | Nom du fichier. |

## Membres

### GET user/categories

_(Depuis la version 1.4.0)_

Renvoie la liste des catégories de membres, triée par nom, et incluant le nombre de membres de la catégorie (dans la clé `count`).

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

_(Depuis la version 1.4.0)_

Exporte la liste des membres d'une catégorie.

| Paramètre | Type | Description |
| :- | :- | :- |
| `ID` | `int` | Identifiant unique de la catégorie. |
| `FORMAT` | `string:enum(json, csv, ods, xlsx)` | Format de sortie. |

### user/new (POST)

_(Depuis la version 1.4.0)_

Permet de créer un nouveau membre.

| Paramètre | Type | Description |
| :- | :- | :- |
| `id_category` | `int` | Identifiant de la catégorie. Si absent, la catégorie par défaut sera utilisée. |
| `password` | `string` | Mot de passe du membre. |
| `force_duplicate` | `bool` | Si `true` ou `1`, alors aucune erreur ne sera renvoyée si le nom du membre correspond à un membre déjà existant. |

Attention, cette méthode comporte des restrictions :

* il n'est pas possible de créer un membre dans une catégorie ayant accès à la configuration
* il n'est pas possible de définir l'OTP ou la clé PGP du membre créé
* seul un identifiant API ayant le droit "Administration" pourra créer des membres administrateurs

Il est possible d'utiliser tous les champs de la fiche membre en utilisant la clé unique du champ.

Sera renvoyée la liste des infos de la fiche membre.

Si un membre avec le même nom existe déjà (et que `force_duplicate` n'est pas utilisé), une erreur `409` sera renvoyée.

```request
curl -F nom="Bla bla" -F id_category=3 -F password=abcdef123456 https://test:abcd@monpaheko.tld/api/user/new
```

### user/{ID} (GET)

Renvoie les infos de la fiche d'un membre à partir de son ID.

| Paramètre | Type | Description |
| :- | :- | :- |
| `ID` | `int` | Identifiant unique du membre (différent du numéro). |

_(Depuis la version 1.4.0)_

Plusieurs clés supplémentaires sont retournées, en plus des champs de la fiche membre :

* `has_password`
* `has_pgp_key`
* `has_otp`

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

Supprime un membre à partir de son ID.

| Paramètre | Type | Description |
| :- | :- | :- |
| `ID` | `int` | Identifiant unique du membre (différent du numéro). |

_(Depuis la version 1.4.0)_

Seuls les identifiants d'API ayant le droit "Administration" pourront supprimer des membres.

Note : il n'est pas possible de supprimer via l'API un membre appartenant à une catégorie ayant accès à la configuration.

### POST user/{ID}

Modifie les infos de la fiche d'un membre à partir de son ID.

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

Permet d'importer un fichier de tableur (CSV/XLSX/ODS) de la liste des membres.

| Paramètre | Type | Description |
| :- | :- | :- |
| `mode` | `string:enum(create, update, auto)` | Mode d'import du fichier. Voir ci-dessous pour les détails. _(Depuis la version 1.2.8)_ |
| `skip_lines` | `int` | Nombre de lignes à ignorer. Défaut : `1`. |
| `column[x]` | `string` | Correspondance entre la colonne numéro `x` (commence à zéro) et le champ de la fiche membre. |


Cette route nécessite une clé d'API ayant les droits d'administration, car importer un fichier peut permettre de modifier l'identifiant de connexion d'un administrateur et donc potentiellement d'obtenir l'accès à l'interface d'administration.

Le paramètre `mode` permet d'utiliser une de ces options pour spécifier le mode d'import :

* `auto` (défaut si le mode n'est pas spécifié) : met à jour la fiche d'un membre si son numéro existe, sinon crée un membre si le numéro de membre indiqué n'existe pas ou n'est pas renseigné
* `create` : ne fait que créer de nouvelles fiches de membre, si le numéro de membre existe déjà une erreur sera produite
* `update` : ne fait que mettre à jour les fiches de membre en utilisant le numéro de membre comme référence, si le numéro de membre n'existe pas une erreur sera produite

```request
curl https://test:abcd@monpaheko.tld/api/user/import \
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
curl https://test:abcd@monpaheko.tld/api/user/import -F file=@membres.csv
```

### PUT user/import

Permet d'importer un fichier de tableur (CSV/XLSX/ODS) de la liste des membres.

Identique à la même méthode en `POST`, mais les paramètres sont passés dans l'URL, et le fichier en contenu de la requête.

```request
curl https://test:abcd@monpaheko.tld/api/user/import?mode=create&column[0]=nom_prenom&skip_lines=0 \
  -T membres.csv
```

### POST user/import/preview

Identique à `user/import`, mais l'import n'est pas enregistré. À la place l'API indique les modifications qui seraient apportées.

Renvoie un objet JSON comme ceci :

* `errors` : liste des erreurs d'import
* `created` : liste des membres ajoutés, chaque objet contenant tous les champs de la fiche membre qui serait créée
* `modified` : liste des membres modifiés, chaque membre aura une clé `id` et une clé `name`, ainsi qu'un objet `changed` contenant la liste des champs modifiés. Chaque champ modifié aura 2 propriétés `old` et `new`, contenant respectivement l'ancienne valeur du champ et la nouvelle.
* `unchanged` : liste des membres mentionnés dans l'import, mais qui ne seront pas affectés. Pour chaque membre une clé `name` et une clé `id` indiquant le nom et l'identifiant unique numérique du membre

Note : si `errors` n'est pas vide, alors il sera impossible d'importer le fichier avec `user/import`.

```request
curl https://test:abcd@monpaheko.tld/api/user/import/preview -F mode=update -F file=@/tmp/membres.csv
```

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

Idem quel la méthode en `POST` mais les paramètres sont passés dans l'URL et le fichier dans le corps de la requête.

## Activités

### PUT services/subscriptions/import

Permet d'importer les inscriptions des membres aux activités à partir d'un fichier CSV.

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
curl https://test:abcd@monpaheko.tld/api/services/subscriptions/import -T /tmp/inscriptions.csv
```

## Erreurs

Paheko dispose d'un système dédié à la gestion des erreurs internes, compatible avec les formats des logiciels AirBrake et errbit.

### POST errors/report

Permet d'envoyer un rapport d'erreur (au format airbrake/errbit/Paheko), comme si c'était une erreur locale.

Utile pour centraliser les erreurs de plusieurs instances.

### GET errors/log

Renvoie le log d'erreurs système, au format airbrake/errbit ([voir la doc AirBrake pour un exemple du format](https://airbrake.io/docs/api/#create-notice-v3))

## Comptabilité

### GET accounting/years

Renvoie la liste des exercices.

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

Renvoie la liste des plans comptables.

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

Renvoie la liste des comptes pour le plan comptable indiqué.

| Paramètre | Type | Description |
| :- | :- | :- |
| `ID_CHART` | `int` | ID du plan comptable. |

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

Renvoie le journal général des écritures de l'exercice indiqué. 

| Paramètre | Type | Description |
| :- | :- | :- |
| `ID_YEAR` | `int|string` | ID de l'exercice, ou `current`. |

Note : il est possible d'utiliser `current` comme paramètre pour `{ID_YEAR}` pour désigner l'exercice ouvert en cours. S'il y a plusieurs exercices ouverts, alors celui qui est le plus proche de la date actuelle sera utilisé.

### GET accounting/years/{ID_YEAR}/export/{TYPE}.{FORMAT} (GET)

Exporte l'exercice indiqué au format indiqué.

| Paramètre | Type | Description |
| :- | :- | :- |
| `ID_YEAR` | `int|string` | ID de l'exercice, ou `current`. |
| `TYPE` | `string:enum(full, grouped, simple, fec)` | Type d'export. `simple` ne contient pas les écritures avancées. |
| `FORMAT` | `string:enum(csv, ods, xlsx, json)` | Format d'export. |

_(Depuis la version 1.4.0)_

### GET accounting/years/{ID_YEAR}/journal/{CODE}

Renvoie le journal des écritures d'un compte pour l'exercice indiqué.

| Paramètre | Type | Description |
| :- | :- | :- |
| `ID_YEAR` | `int|string` | ID de l'exercice, ou `current`. |
| `CODE` | `int|string` | Code du compte. |

### GET accounting/years/{ID_YEAR}/journal/={ID}

Renvoie le journal des écritures d'un compte pour l'exercice indiqué.

| Paramètre | Type | Description |
| :- | :- | :- |
| `ID_YEAR` | `int|string` | ID de l'exercice, ou `current`. |
| `ID` | `int` | ID du compte. |

### accounting/transaction/{ID_TRANSACTION} (GET)

Renvoie les détails de l'écriture indiquée.

### accounting/transaction/{ID_TRANSACTION} (POST)

Modifie l'écriture indiquée. Voir plus bas le format attendu.

### accounting/transaction/{ID_TRANSACTION}/users (GET)

Renvoie la liste des membres liés à une écriture.

### accounting/transaction/{ID_TRANSACTION}/users (POST)

Met à jour la liste des membres liés à une écriture, en utilisant les ID de membres passés dans un tableau nommé `users`.

```
 curl -v "http://…/api/accounting/transaction/9337/users"  -F 'users[]=2'
```

### accounting/transaction/{ID_TRANSACTION}/users (DELETE)

Efface la liste des membres liés à une écriture.

### accounting/transaction/{ID_TRANSACTION}/subscriptions (GET)

_(Depuis la version 1.4.0)_

Renvoie la liste des inscriptions (aux activités) liées à une écriture.

### accounting/transaction/{ID_TRANSACTION}/subscriptions (POST)

_(Depuis la version 1.4.0)_

Met à jour la liste des inscriptions liées à une écriture, en utilisant les ID d'inscriptions passés dans un tableau nommé `subscriptions`.

```
 curl -v "http://…/api/accounting/transaction/9337/subscriptions"  -F 'subscriptions[]=2'
```

### accounting/transaction/{ID_TRANSACTION}/subscriptions (DELETE)

_(Depuis la version 1.4.0)_

Efface la liste des inscriptions liées à une écriture.

### accounting/transaction (POST)

Crée une nouvelle écriture, renvoie les détails si l'écriture a été créée. Voir plus bas le format attendu.

#### Structure pour créer / modifier une écriture

Les champs à spécifier pour créer ou modifier une écriture sont les suivants :

* `id_year`
* `date` (format YYYY-MM-DD)
* `type` peut être un type d'écriture simplifié (2 lignes) : `EXPENSE` (dépense), `REVENUE` (recette), `TRANSFER` (virement), `DEBT` (dette), `CREDIT` (créance), ou `ADVANCED` pour une écriture multi-ligne
* `amount` (uniquement pour les écritures simplifiées) : contient le montant de l'écriture
* `credit` (uniquement pour les écritures simplifiées) : contient le numéro du compte porté au crédit
* `debit` (uniquement pour les écritures simplifiées) : contient le numéro du compte porté au débit
* `lines` (pour les écritures multi-lignes) : un tableau dont chaque ligne doit contenir :
  * `account` (numéro du compte) ou `id_account` (ID unique du compte)
  * `credit` : montant à inscrire au crédit (doit être zéro ou non renseigné si `debit` est renseigné, et vice-versa)
  * `debit` : montant à inscrire au débit
  * `label` (facultatif) : libellé de la ligne
  * `reference` (facultatif) : référence de la ligne (aussi appelé référence du paiement pour les écritures simplifiées)
  * `id_project` : ID unique du projet à affecter

Champs optionnels :

* `reference` : numéro de pièce comptable
* `notes` : remarques (texte multi ligne)
* `id_project` : ID unique du projet à affecter (pour les écritures simplifiées uniquement)
* `payment_reference` (uniquement pour les écritures simplifiées) : référence de paiement
* `linked_users` : Tableau des IDs des membres à lier à l'écriture *(depuis 1.3.3)*
* `linked_transactions` : Tableau des IDs des écritures à lier à l'écriture *(depuis 1.3.5)*
* `linked_subscriptions` : Tableau des IDs des inscriptions à lier à l'écriture *(depuis 1.4.0)*

Exemple :

```
curl -F 'id_year=12' -F 'label=Test' -F 'date=01/02/2022' -F 'type=EXPENSE' -F 'amount=42' -F 'debit=512A' -F 'credit=601' …
```