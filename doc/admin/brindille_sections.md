Title: Référence des sections Brindille

{{{.nav
* [Documentation Brindille](brindille.html)
* [Fonctions](brindille_functions.html)
* **[Sections](brindille_sections.html)**
* [Filtres](brindille_modifiers.html)
}}}

<<toc aside level=2>>

# Sections généralistes

## foreach

Permet d'itérer sur un tableau par exemple. Ainsi chaque élément du tableau exécutera une fois le contenu de la section.

| Paramètre | Optionnel / obligatoire ? | Fonction |
| :- | :- | :- |
| `from` | **obligatoire** | Variable sur laquelle effectuer l'itération |
| `key` | **optionnel** | Nom de la variable à utiliser pour la clé de l'élément |
| `value` | **optionnel** | Nom de la variable à utiliser pour la valeur de l'élément |

Considérons ce tableau :

```
{{:assign var="tableau" a="bleu" b="orange"}}
```

On peut alors itérer pour récupérer les clés (`a` et `b` ainsi que les valeurs `bleu` et `orange`) :

```
{{#foreach from=$tableau key="key" item="value"}}
{{$key}} = {{$value}}
{{/foreach}}
```

Cela affichera :

```
a = bleu
b = orange
```

Si on a un tableau à plusieurs niveaux, les éléments du tableau sont automatiquement transformés en variable :

```
{{:assign var="tableau.a" couleur="bleu"}}
{{:assign var="tableau.b" couleur="orange"}}
```

```
{{#foreach from=$variable}}
{{$couleur}}
{{/foreach}}
```

Affichera :

```
bleu
orange
```

## restrict

Permet de limiter (restreindre) une partie de la page aux membres qui sont connectés et/ou qui ont certains droits.

Deux paramètres optionnels peuvent être utilisés ensemble (il n'est pas possible d'utiliser seulement un des deux) :

| Paramètre | Optionnel / obligatoire ? | Fonction |
| :- | :- | :- |
| `level` | *optionnel* | Niveau d'accès : `read`, `write`, `admin` |
| `section` | *optionnel* | Section où le niveau d'accès doit s'appliquer : `users`, `accounting`, `web`, `documents`, `config` |
| `block` | *optionnel* | Si ce paramètre est présent et vaut `true`, alors l'accès sera interdit si les conditions d'accès demandées ne sont pas remplies : une page d'erreur sera renvoyée. |

Exemple pour voir si un membre est connecté :

```
{{#restrict}}
	Un membre est connecté, mais on ne sait pas avec quels droits.
{{else}}
	Aucun membre n'est connecté.
{{/restrict}}
```

Exemple pour voir si un membre qui peut administrer les membres est connecté :

```
{{#restrict section="users" level="admin"}}
	Un membre est connecté, et il a le droit d'administrer les membres.
{{else}}
	Aucun membre n'est connecté, ou un membre est connecté mais n'est pas administrateur des membres.
{{/if}}
```

Pour bloquer l'accès aux membres non connectés, ou qui n'ont pas accès en écriture à la comptabilité.

```
{{#restrict block=true section="accounting" level="write"}}
{{/restrict}}
```

Le mieux est de mettre ce code au début d'un squelette.

# Requêtes SQL

## select

Exécute une requête SQL `SELECT` et effectue une itération pour chaque résultat de la requête.

Pour une utilisation plus simplifiée des requêtes, voir aussi la section [sql](#sql).

Attention : la syntaxe de cette section est différente des autres sections Brindille. En effet après le début (`{{#select`) doit suivre la suite de la requête, et non pas les paramètres :

```
Liste des membres inscrits à la lettre d'informations :
{{#select nom, prenom FROM users WHERE lettre_infos = 1;}}
    - {{prenom}} {{$nom}}<br />
{{else}}
    Aucun membre n'est inscrit à la lettre d'information.
{{/select}}
```

Des paramètres nommés de SQL peuvent être présentés après le point-virgule marquant la fin de la requête SQL :

```
{{:assign prenom="Karim"}}
{{#select * FROM users WHERE prenom = :prenom;
    :prenom=$prenom}}
...
{{/select}}
```

Notez les deux points avant le nom du paramètre. Ces paramètres sont protégés contre les injections SQL (généralement appelés paramètres nommés).

Pour intégrer des paramètres qui ne sont pas protégés (**attention !**), il faut utiliser le point d'exclamation :

```
{{:assign var="categories." value=1}}
{{:assign var="categories." value=2}}
{{#select * FROM users WHERE !categories;
    !categories='id_category'|sql_where:'IN':$categories}}
```

Cela créera la requête suivante : `SELECT * FROM users WHERE id_category IN (1, 2);`

Il est aussi possible d'intégrer directement des variables dans la requête, en utilisant la syntaxe `{$variable|filtre:argument1:argument2}`, comme une variable classique donc, mais au lieu d'utiliser des doubles accolades, on utilise ici des accolades simples. Ces variables seront automatiquement protégées contre les injections SQL.

```
{{:assign prenom="Camille"}}
{{#select * FROM users WHERE initiale_prenom = {$prenom|substr:0:1};}}
```

Cependant, pour plus de lisibilité il est conseillé d'utiliser la syntaxe des paramètres nommés SQL (voir ci-dessus).

Il est aussi possible d'insérer directement du code SQL (attention aux problèmes de sécurité dans ce cas !), pour cela il faut rajouter un point d'exclamation après l'accolade ouvrante :

```
{{:assign var="prenoms." value="Karim"}}
{{:assign var="prenoms." value="Camille"}}
{{#select * FROM users WHERE {!"prenom"|sql_where:"IN":$prenoms};}}
...
{{/select}}
```

Il est aussi possible d'utiliser les paramètres suivants :

| Paramètre | Fonction |
| :- | :- |
| `debug` | Si ce paramètre existe, la requête SQL exécutée sera affichée avant le début de la boucle. |
| `explain` | Si ce paramètre existe, l'explication de la requête SQL exécutée sera affichée avant le début de la boucle. | 
| `assign` | Si renseigné, une variable de ce nom sera créée, et le contenu de la dernière ligne du résultat y sera assigné. | 

Exemple avec `debug` :

```
{{:assign prenom="Karim"}}
{{#select * FROM users WHERE prenom = :prenom; :prenom=$prenom debug=true}}
...
{{/select}}
```

Affichera : `SELECT * FROM users WHERE nom = 'Karim'`.

Exemple avec `assign` :

```
{{#select * FROM users WHERE prenom = 'Camille' LIMIT 1; assign="membre"}}{{/select}}
{{$membre.nom}}
```

## sql


Effectue une requête SQL de type `SELECT` dans la base de données, mais de manière simplifiée par rapport à `select`.

```
{{#sql select="*, julianday(date) AS day" tables="membres" where="id_categorie = :id_categorie" :id_categorie=$_GET.id_categorie order="numero DESC" begin=":page*100" limit=100 :page=$_GET.page}}
…
{{/sql}}
```

| Paramètre | Optionnel / obligatoire ? | Fonction |
| :- | :- | :- |
| `tables` | **obligatoire** | Liste des tables à utiliser dans la requête (séparées par des virgules). |
| `select` | *optionnel* | Liste des colonnes à sélectionner, si non spécifié, toutes les colonnes (`*`) seront sélectionnées |

### Sections qui héritent de `sql`

Certaines sections (voir plus bas) héritent de `sql` et rajoutent des fonctionnalités. Dans toutes ces sections, il est possible d'utiliser les paramètres suivants :

| Paramètre | Fonction |
| :- | :- |
| `where` | Condition de sélection des résultats |
| `begin` | Début des résultats, si vide une valeur de `0` sera utilisée. |
| `limit` | Limitation des résultats. Si vide, une valeur de `1000` sera utilisée. |
| `group` | Contenu de la clause `GROUP BY` |
| `order` | Ordre de tri des résultats. Si vide le tri sera fait par ordre d'ajout dans la base de données. |
| `assign` | Si renseigné, une variable de ce nom sera créée, et le contenu de la dernière ligne du résultat y sera assigné. | 
| `debug` | Si ce paramètre existe, la requête SQL exécutée sera affichée avant le début de la boucle. |
| `explain` | Si ce paramètre existe, l'explication de la requête SQL exécutée sera affichée avant le début de la boucle. | 

Il est également possible de passer des arguments dans les paramètres à l'aides des arguments nommés qui commencent par deux points `:` :

```
{{#articles where="title = :montitre" :montitre="Actualité"}}
```

# Pour le site web

## breadcrumbs

Permet de récupérer la liste des pages parentes d'une page afin de constituer un [fil d'ariane](https://fr.wikipedia.org/wiki/Fil_d'Ariane_(ergonomie)) permettant de remonter dans l'arborescence du site

Un seul paramètre est possible :

| Paramètre | Fonction |
| :- | :- |
| `path` (obligatoire) | Chemin (adresse unique) de la page parente |

Chaque itération renverra trois variables :

| Variable | Contenu |
| :- | :- |
| `$title` | Titre de la page ou catégorie |
| `$url` | Adresse HTTP de la page ou catégorie |
| `$path` | Chemin (adresse unique) de la page ou catégorie |

### Exemple

```
<ul>
{{#breadcrumbs path=$page.path}}
	<li>{{$title}}</li>
{{/breadcrumbs}}
</ul>
```

## pages, articles, categories <sup>(sql)</sup>

Note : ces sections héritent de `sql` (voir plus haut).

* `pages` renvoie une liste de pages, qu'elles soient des articles ou des catégories
* `categories` ne renvoie que des catégories
* `articles` ne renvoie que des articles

À part cela ces trois types de section se comportent de manière identique.

| Paramètre | Fonction |
| :- | :- |
| `search` | Renseigner ce paramètre avec un terme à rechercher dans le texte ou le titre. Dans ce cas par défaut le tri des résultats se fait sur la pertinence, sauf si le paramètre `order` est spécifié. Dans ce cas une variable `$snippet` sera disponible à l'intérieur de la boucle, contenant les termes trouvés. |
| `future` | Renseigner ce paramètre à `false` pour que les articles dont la date est dans le futur n'apparaissent pas, `true` pour ne renvoyer QUE les articles dans le futur, et `null` (ou ne pas utiliser ce paramètre) pour que tous les articles, passés et futur, apparaissent. |
| `parent` | Chemin (path) de la catégorie parente. Exemple pour renvoyer la liste des articles de la sous-catégorie "Événements" de la catégorie "Notre atelier" :  `atelier-velo/evenements`. Utiliser `null` pour n'afficher que les articles ou catégories de la racine du site. |

## files, documents, images <sup>(sql)</sup>

Note : ces sections héritent de `sql` (voir plus haut).

* `files` renvoie une liste de fichiers
* `documents` renvoie une liste de fichiers qui ne sont pas des images
* `images` renvoie une liste de fichiers qui sont des images

À part cela ces trois types de section se comportent de manière identique.

Note : seul les fichiers de la section site web sont accessibles, les fichiers de membres, de comptabilité, etc. ne sont pas disponibles.

| Paramètre | Optionnel / obligatoire ? | Fonction |
| :- | :- | :- |
| `parent` | **obligatoire** | Chemin (adresse unique) de l'article ou catégorie parente dont ont veut lister les fichiers |
| `except_in_text` | *optionnel* | passer `true` à ce paramètre , et seuls les fichiers qui ne sont pas liés dans le texte de la page seront renvoyés |

# Sections relatives aux modules

## load <sup>(sql)</sup>

Note : cette section hérite de `sql` (voir plus haut).

Charge un ou des documents pour le module courant.

| Paramètre | Optionnel / obligatoire ? | Fonction |
| :- | :- | :- |
| `module` | optionnel | Nom unique du module lié (par exemple : `recu_don`). Si non spécifié, alors le nom du module courant sera utilisé. |
| `key` | optionnel | Clé unique du document |
| `id` | optionnel | Numéro unique du document |

Il est possible d'utiliser un paramètre commençant par un dollar : `$.cle="valeur"`. Cela va comparer `"valeur"` avec la valeur de la clé `cle` dans le document JSON. C'est l'équivalent d'écrire `where="json_extract(document, '$.cle') = 'valeur'"`.

Pour des conditions plus complexes qu'une simple égalité, il est possible d'utiliser la syntaxe courte `$$…` dans le paramètre `where`. Ainsi `where="$$.nom LIKE 'Bourse%'` est l'équivalent de `where="json_extract(document, '$.nom') LIKE 'Bourse%'"`.

Voir [la documentation de SQLite pour plus de détails sur la syntaxe de json_extract](https://www.sqlite.org/json1.html#jex).

Note : un index SQL dynamique est créé pour chaque requête utilisant une clause `json_extract`.

Chaque itération renverra ces deux variables :

| Variable | Valeur |
| :- | :- |
| `$key` | Clé unique du document |
| `$id` | Numéro unique du document |

Ainsi que chaque élément du document JSON lui-même.

### Exemples

Afficher le nom du document dont la clé est `facture_43` :

```
{{#load key="facture_43"}}
{{$nom}}
{{/load}}
```

Afficher la liste des devis du module `invoice` depuis un autre module par exemple :

```
{{#load module="invoice" $.type="quote"}}
<h1>Titre du devis : {{$subject}}</h1>
<h2>Montant : {{$total}}</h2>
{{/load}}
```

## list

Attention : cette section n'hérite **PAS de `sql`**.

Un peu comme `{{#load}}` cette section charge les documents d'un module, mais au sein d'une liste (tableau HTML).

Cette liste gère automatiquement l'ordre selon les préférences des utilisateurs, ainsi que la pagination.

Cette section est très puissante et permet de générer des listes simplement, une fois qu'on a saisi la logique de son fonctionnement.

| Paramètre | Optionnel / obligatoire ? | Fonction |
| :- | :- | :- |
| `schema` | **requis** si `select` n'est pas fourni | Chemin vers un fichier de schéma JSON qui représenterait le document |
| `select` | **requis** si `schema` n'est pas fourni | Liste des colonnes à sélectionner, sous la forme `$$.colonne AS "Colonne"`, chaque colonne étant séparée par un point-virgule. |
| `module` | *optionnel* | Nom unique du module lié (par exemple : `recu_don`). Si non spécifié, alors le nom du module courant sera utilisé. |
| `columns` | *optionnel* | Permet de n'afficher que certaines colonnes du schéma. Indiquer ici le nom des colonnes, séparées par des virgules. |
| `order` | *optionnel* | Colonne utilisée par défaut pour le tri (si l'utilisateur n'a pas choisi le tri sur une autre colonne). Si `select` est utilisé, il faut alors indiquer ici le numéro de la colonne, et non pas son nom. |
| `desc` | *optionnel* | Si ce paramètre est à `true`, l'ordre de tri sera inversé. |
| `max` | *optionnel* | Nombre d'éléments à afficher dans la liste, sur chaque page. |
| `where` | *optionnel* | Condition `WHERE` de la requête SQL. |
| `debug` | *optionnel* | Si ce paramètre existe, la requête SQL exécutée sera affichée avant le début de la boucle. |
| `explain` | *optionnel* | Si ce paramètre existe, l'explication de la requête SQL exécutée sera affichée avant le début de la boucle. | 

Pour déterminer quelles colonnes afficher dans le tableau, il faut utiliser soit le paramètre `schema` pour indiquer un fichier de schéma JSON qui sera utilisé pour donner le libellé des colonnes (via la `description` indiquée dans le schéma), soit le paramètre `select`, où il faut alors indiquer le nom et le libellé des colonnes sous la forme `$$.colonne1 AS "Libellé"; $$.colonne2 AS "Libellé 2"`.

Comme pour `load`, il est possible d'utiliser un paramètre commençant par un dollar : `$.cle="valeur"`. Cela va comparer `"valeur"` avec la valeur de la clé `cle` dans le document JSON. C'est l'équivalent d'écrire `where="json_extract(document, '$.cle') = 'valeur'"`.

Pour des conditions plus complexes qu'une simple égalité, il est possible d'utiliser la syntaxe courte `$$…` dans le paramètre `where`. Ainsi `where="$$.nom LIKE 'Bourse%'` est l'équivalent de `where="json_extract(document, '$.nom') LIKE 'Bourse%'"`.

Voir [la documentation de SQLite pour plus de détails sur la syntaxe de json_extract](https://www.sqlite.org/json1.html#jex).

Note : un index SQL dynamique est créé pour chaque requête utilisant une clause `json_extract`.

Chaque itération renverra toujours ces deux variables :

| Variable | Valeur |
| :- | :- |
| `$key` | Clé unique du document |
| `$id` | Numéro unique du document |

Ainsi que chaque élément du document JSON lui-même.

La section ouvre un tableau HTML et le ferme automatiquement, donc le contenu de la section **doit** être une ligne de tableau HTML (`<tr>`).

Dans chaque ligne du tableau il faut respecter l'ordre des colonnes indiqué dans `columns` ou `select`. Une dernière colonne est réservée aux boutons d'action : `<td class="actions">...</td>`.

### Exemples

Lister le nom, la date et le montant des reçus fiscaux, à partir du schéma JSON suivant :

```
{
	"$schema": "https://json-schema.org/draft/2020-12/schema",
	"type": "object",
	"properties": {
		"date": {
			"description": "Date d'émission",
			"type": "string",
			"format": "date"
		},
		"adresse": {
			"description": "Adresse du bénéficiaire",
			"type": "string"
		},
		"nom": {
			"description": "Nom du bénéficiaire",
			"type": "string"
		},
		"montant": {
			"description": "Montant",
			"type": "integer",
			"minimum": 0
		}
	}
}
```

Le code de la section sera alors comme suivant :

```
{{#list schema="./recu.schema.json" columns="nom, date, montant"}}
	<tr>
		<th>{{$nom}}</th>
		<td>{{$date|date_short}}</td>
		<td>{{$montant|raw|money_currency}}</td>
		<td class="actions">
			{{:linkbutton shape="eye" label="Ouvrir" href="./voir.html?id=%d"|args:$id target="_dialog"}}
		</td>
	</tr>
{{else}}
	<p class="alert block">Aucun reçu n'a été trouvé.</p>
{{/list}}
```

Si le paramètre `columns` avait été omis, la colonne `adresse` aurait également été incluse.

Il est à noter que si l'utilisation directe du schéma est bien pratique, cela ne permet pas de récupérer des informations plus complexes dans la structure JSON, par exemple une sous-clé ou l'application d'une fonction SQL. Dans ce cas il faut obligatoirement utiliser `select`. Par exemple ici on veut pouvoir afficher l'année, et trier sur l'année par défaut :

```
{{#list select="$$.nom AS 'Nom du donateur' ; strftime('%Y', $$.date) AS 'Année'" order=2}}
	<tr>
		<th>{{$nom}}</th>
		<td>{{$col2}}</td>
		<td class="actions">
			{{:linkbutton shape="eye" label="Ouvrir" href="./voir.html?id=%d"|args:$id target="_dialog"}}
		</td>
	</tr>
{{else}}
	<p class="alert block">Aucun reçu n'a été trouvé.</p>
{{/list}}
```

On peut utiliser le nom des clés du document JSON, mais sinon pour faire référence à la valeur d'une colonne spécifique dans la boucle, il faut utiliser son numéro d'ordre (qui commence à `1`, pas zéro). Ici on veut afficher l'année, donc la seconde colonne, donc `$col1`.

Noter aussi l'utilisation du numéro de la colonne de l'année (`2`) pour le paramètre `order`, qui avec `select` doit indiquer le numéro de la colonne à utiliser pour l'ordre.


## users

Liste les membres.

Paramètres possibles :

| `id` | optionnel | Identifiant unique du membre |

Chaque itération renverra la fiche du membre, ainsi que ces variables :

| `$id` | Identifiant unique du membre |
| `$user_name` | Nom du membre, tel que défini dans la configuration |
| `$user_login` | Identifiant de connexion du membre, tel que défini dans la configuration |


## subscriptions

Liste les inscriptions à une ou des activités.

Paramètres possibles :

| `user` | optionnel | Identifiant unique du membre |
| `active` | optionnel | Si `TRUE`, seules les inscriptions à jour sont listées |