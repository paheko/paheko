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

## sql (POST)

Permet d'exécuter une requête SQL `SELECT` (uniquement, pas de requête UPDATE, DELETE, INSERT, etc.) sur la base de données. La requête SQL doit être passée dans le corps de la requête HTTP, ou dans le paramètre `sql`. Le résultat est retourné dans la clé `results` de l'objet JSON.

S'il n'y a pas de limite à la requête, une limite à 1000 résultats sera ajoutée obligatoirement.

```
curl https://test:abcd@paheko.monasso.tld/api/sql/ -d 'SELECT * FROM membres LIMIT 5;'
```

**ATTENTION :** Les requêtes en écriture (`INSERT, DELETE, UPDATE, CREATE TABLE`, etc.) ne sont pas acceptées, il n'est pas possible de modifier la base de données directement via Paheko, afin d'éviter les soucis de données corrompues.

Depuis la version 1.2.8, il est possible d'utiliser le paramètre `format` pour choisir le format renvoyé :

* `json` (défaut) : renvoie un objet JSON, dont la clé est `"results"` et contient un tableau de la liste des membres trouvés
* `csv` : renvoie un fichier CSV
* `ods` : renvoie un tableau LibreOffice Calc (ODS)
* `xlsx` : renvoie un tableau Excel (XLSX)

Exemple :

```
curl https://test:abcd@paheko.monasso.tld/api/sql/ -F sql='SELECT * FROM membres LIMIT 5;' -F format=csv
```

## download (GET)

Télécharge la base de données complète. Renvoie directement le fichier SQLite de la base de données.

## Site web

### web/list (GET)

Renvoie la liste des pages du site web.

### web/attachment/{PAGE_URI}/{FILENAME} (GET)

Renvoie le fichier joint correspondant à la page et nom de fichier indiqués.

### web/page/{PAGE_URI} (GET)

Renvoie un objet JSON avec toutes les infos de la page donnée.

Rajouter le paramètre `?html` à l'URL pour obtenir en plus une clé `html` dans l'objet JSON qui contiendra la page au format HTML.

### web/html/{PAGE_URI} (GET)

Renvoie uniquement le contenu de la page au format HTML.

## Membres

### user/import (PUT)

Permet d'importer un fichier de tableur (CSV/XLSX/ODS) de la liste des membres, comme si c'était fait depuis l'interface de Paheko.

Cette route nécessite une clé d'API ayant les droits d'administration, car importer un fichier peut permettre de modifier l'identifiant de connexion d'un administrateur et donc potentiellement d'obtenir l'accès à l'interface d'administration.

Paheko s'attend à ce que la première est ligne du tableau contienne le nom des colonnes, et que le nom des colonnes correspond au nom des champs de la fiche membre (ou à leur nom unique). Par exemple si votre fiche membre contient les champs *Nom et prénom* et *Adresse postale*, alors le fichier fourni devra ressembler à ceci :

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
curl https://test:abcd@monpaheko.tld/api/user/import -T membres.csv
```

#### Paramètres

Les paramètres sont à spécifier dans l'URL, dans la query string.

Depuis la version 1.2.8 il est possible d'utiliser un paramètre supplémentaire `mode` contenant une de ces options pour spécifier le mode d'import :

* `auto` (défaut si le mode n'est pas spécifié) : met à jour la fiche d'un membre si son numéro existe, sinon crée un membre si le numéro de membre indiqué n'existe pas ou n'est pas renseigné
* `create` : ne fait que créer de nouvelles fiches de membre, si le numéro de membre existe déjà une erreur sera produite
* `update` : ne fait que mettre à jour les fiches de membre en utilisant le numéro de membre comme référence, si le numéro de membre n'existe pas une erreur sera produite

Depuis la version 1.3.0 il est possible de spécifier :

* le nombre de lignes à ignorer avec le paramètre `skip_lines=X` : elles ne seront pas importées. Par défaut la première ligne est ignorée.
* la correspondance des colonnes avec des paramètres `column[x]` ou `x` est le numéro de la colonne (la numérotation commence à zéro), et la valeur contient le nom unique du champ de la fiche membre.

Exemple :

```
curl https://test:abcd@monpaheko.tld/api/user/import?mode=create&column[0]=nom_prenom&column[1]=code_postal&skip_lines=0 -T membres.csv
```

### user/import (POST)

Identique à la même méthode en `PUT`, mais les paramètres sont passés dans le corps de la requête, avec le fichier, dont le nom sera alors `file`.

```
curl https://test:abcd@monpaheko.tld/api/user/import \
  -F mode=create \
  -F 'column[0]=nom_prenom' \
  -F 'column[1]=code_postal' \
  -F skip_lines=0 \
  -F file=@membres.csv
```

### user/import/preview (PUT)

Identique à `user/import`, mais l'import n'est pas enregistré, et la route renvoie les modifications qui seraient effectuées en important le fichier :

* `errors` : liste des erreurs d'import
* `created` : liste des membres ajoutés, chaque objet contenant tous les champs de la fiche membre qui serait créée
* `modified` : liste des membres modifiés, chaque membre aura une clé `id` et une clé `name`, ainsi qu'un objet `changed` contenant la liste des champs modifiés. Chaque champ modifié aura 2 propriétés `old` et `new`, contenant respectivement l'ancienne valeur du champ et la nouvelle.
* `unchanged` : liste des membres mentionnés dans l'import, mais qui ne seront pas affectés. Pour chaque membre une clé `name` et une clé `id` indiquant le nom et l'identifiant unique numérique du membre

Note : si `errors` n'est pas vide, alors il sera impossible d'importer le fichier avec `user/import`.

Exemple de retour :

```
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


### user/import/preview (POST)

Idem quel la méthode en `PUT` mais accepte les paramètres dans le corps de la requête (voir ci-dessus).

## Activités

### services/subscriptions/import (PUT)

_(Depuis Paheko 1.3.2)_

Permet d'importer les inscriptions des membres aux activités à partir d'un fichier CSV. Les activités et tarifs doivent déjà exister avant l'import.

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

### errors/report (POST)

Permet d'envoyer un rapport d'erreur (au format airbrake/errbit/Paheko), comme si c'était une erreur locale.

### errors/log (GET)

Renvoie le log d'erreurs système, au format airbrake/errbit ([voir la doc AirBrake pour un exemple du format](https://airbrake.io/docs/api/#create-notice-v3))

## Comptabilité

### accounting/years (GET)

Renvoie la liste des exercices.

### accounting/charts (GET)

Renvoie la liste des plans comptables.

### accounting/charts/{ID_CHART}/accounts (GET)

Renvoie la liste des comptes pour le plan comptable indiqué (voir `id_chart` dans la liste des exercices).

### accounting/years/{ID_YEAR}/journal (GET)

Renvoie le journal général des écritures de l'exercice indiqué. 

Note : il est possible d'utiliser `current` comme paramètre pour `{ID_YEAR}` pour désigner l'exercice ouvert en cours. S'il y a plusieurs exercices ouverts, alors le plus ancien sera choisi.

### accounting/years/{ID_YEAR}/account/journal (GET)

Renvoie le journal des écritures d'un compte pour l'exercice indiqué.

Le compte est spécifié soit via le paramètre `code`, soit via le paramètre `id`. Exemple :  `/accounting/years/4/account/journal?code=512A`

Note : il est possible d'utiliser `current` comme paramètre pour `{ID_YEAR}` pour désigner l'exercice ouvert en cours. S'il y a plusieurs exercices ouverts, alors le plus ancien sera choisi.

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
* `linked_users` : ID des membres à lier à l'écriture *(depuis 1.3.3)*

Exemple :

```
curl -F 'id_year=12' -F 'label=Test' -F 'date=01/02/2022' -F 'type=EXPENSE' -F 'amount=42' -F 'debit=512A' -F 'credit=601' …
```