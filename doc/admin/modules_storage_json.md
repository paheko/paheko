Title: Stockage dans un module de documents JSON

{{{.nav
* [Modules](modules.html)
* [Documentation Brindille](brindille.html)
* [Fonctions](brindille_functions.html)
* [Sections](brindille_sections.html)
* [Filtres](brindille_modifiers.html)
}}}

<<toc aside>>

**ATTENTION :** Le stockage de documents JSON dans un module est déprécié depuis la version 1.4.0 de Paheko. Cette logique de fonctionnement est susceptible d'être supprimée à l'avenir, quand tous les modules officiels de Paheko n'utiliseront plus cette logique. Il est donc recommandé de ne plus l'utiliser.

La syntaxe de ces fonctions et sections diffère de celle utilisée pour le stockage sous forme de table SQL. Quand une fonction ou section `save`, `load`, ou `delete` est appelée, la nouvelle syntaxe (liée aux tables SQL) est utilisée à partir du moment où le nom d'une table (paramètre `table`) est spécifié. Sinon c'est la syntaxe liée aux documents JSON (ce document) qui est utilisée.

Cette documentation n'est présente qu'à titre informatif.

Il est recommandé d'utiliser le stockage sous forme de tables SQL.

# Généralités

## Enregistrement

Explication du fonctionnement technique derrière la fonction `save`.

En pratique chaque enregistrement sera placé dans une table SQL dont le nom commence par `module_data_`. Ici la table sera donc nommée `module_data_factures` si le nom unique du module est `factures`.

Le schéma de cette table est le suivant :

```
CREATE TABLE module_data_factures (
  id INTEGER PRIMARY KEY NOT NULL,
  key TEXT NULL,
  document TEXT NOT NULL
);

CREATE UNIQUE INDEX module_data_factures_key ON module_data_factures (key);
```

Comme on peut le voir, chaque ligne dans la table peut avoir une clé unique (`key`), et un ID ou juste un ID auto-incrémenté. La clé unique n'est pas obligatoire, mais peut être utile pour différencier certains documents.

Par exemple le code suivant :

```
{{:save key="facture_43" nom="Facture de courses"}}
```

Est l'équivalent de la requête SQL suivante :

```
INSERT OR REPLACE INTO module_data_factures (key, document) VALUES ('facture_43', '{"nom": "Facture de courses"}');
```

## Récupération et liste de documents

Il sera ensuite possible d'utiliser la boucle `load` pour récupérer les données :

```
{{#load id=42}}
	Ce document est de type {{$type}} créé le {{$date}}.
	<h2>{{$label}}</h2>
	À payer : {{$total}} €
	{{else}}
	Le document numéro 42 n'a pas été trouvé.
{{/load}}
```

Cette boucle `load` permet aussi de faire des recherches sur les valeurs du document :

```
<ul>
{{#load where="$$.type = 'facture'" order="date DESC"}}
	<li>{{$label}} ({{$total}} €)</li>
{{/load}}
</ul>
```

La syntaxe `$$.type` indique d'aller extraire la clé `type` du document JSON.

C'est un raccourci pour la syntaxe SQLite `json_extract(document, '$.type')`.


# Fonctions

## save

Enregistre des données, sous la forme d'un document, dans la base de données, pour le module courant.

| Paramètre | Obligatoire ou optionnel ? | Fonction |
| :- | :- | :- |
| `key` | optionnel | Clé unique du document |
| `id` | optionnel | Numéro unique du document |
| `validate_schema` | optionnel | Fichier de schéma JSON à utiliser pour valider les données avant enregistrement |
| `validate_only` | optionnel | Liste des paramètres à valider (par exemple pour ne faire qu'une mise à jour partielle), séparés par des virgules. |
| `assign_new_id` | optionnel | Si renseigné, le nouveau numéro unique du document sera indiqué dans cette variable. |
| `from` | optionnel | Si renseigné avec un tableau, chaque entrée du tableau sera traitée comme un élément à enregistrer. |
| `replace` | optionnel | (Booléen) Si ce paramètre vaut `true`, alors le contenu du document sera écrasé, au lieu d'être fusionné. |
| … | optionnel | Autres paramètres : traités comme des valeurs à enregistrer dans le document |

Si ni `key` ni `id` ne sont indiqués, un nouveau document sera créé avec un nouveau numéro (ID) unique.

### Mise à jour

Si le document indiqué existe déjà, il sera mis à jour. Les valeurs nulles (`NULL`) seront effacées.

```
{{:save key="facture_43" nom="Atelier mobile" montant=250}}
```

Enregistrera dans la base de données le document suivant sous la clé `facture_43` :

```
{"nom": "Atelier mobile", "montant": 250}
```

Exemple de mise à jour :

```
{{:save key="facture_43" montant=300}}
```

Seul le montant sera modifié, le nom ne sera pas modifié.

Par contre en utilisant le paramètre `replace`, le document sera écrasé :

```
{{:save key="facture_43" replace=true nom="Vente de vélo"}}
```

Donnera :

```
{"nom": "Vente de vélo"}
```

Le montant est donc supprimé.

### Récupérer l'identifiant du document ajouté

Exemple de récupération du nouvel ID :

```
{{:save titre="Coucou !" assign_new_id="id"}}
Le document n°{{$id}} a bien été enregistré.
```

### Enregistrer plusieurs documents en une fois

Le paramètre `from` est équivalent à appeler la fonction `save` dans une boucle. Ainsi au lieu de :

```
{{:assign var="documents." title="Titre 1"}}
{{:assign var="documents." title="Titre 2"}}
{{#foreach from=$documents item="doc"}}
  {{:save title=$doc.title validate_schema="./document.schema.json"}}
{{/foreach}}
```

On peut simplement utiliser :

```
{{:assign var="documents." title="Titre 1"}}
{{:assign var="documents." title="Titre 2"}}
{{:save from=$documents validate_schema="./document.schema.json"}}
```

### Validation avec un schéma JSON

```
{{:save titre="Coucou" texte="Très long" validate_schema="./document.schema.json"}}
```

Pour ne valider qu'une partie du schéma, par exemple si on veut faire une mise à jour du document :

```
{{:save key="test" titre="Coucou" validate_schema="./document.schema.json" validate_only="titre"}}
```


## delete

Supprime un document lié au module courant.

| Paramètre | Obligatoire ou optionnel ? | Fonction |
| :- | :- | :- |
| `key` | optionnel | Clé unique du document |
| `id` | optionnel | Numéro unique du document |

Il est possible de spécifier d'autres paramètres, ou une clause `where` et des paramètres dont le nom commence par deux points.

* Supprimer le document avec la clé `facture_43` : `{{:delete key="facture_43"}}`
* Supprimer le document avec la clé `ABCD` et dont la propriété `type` du document correspond à la valeur `facture` : `{{:delete key="ABCD" type="facture"}}`
* Supprimer tous les documents : `{{:delete}}`
* Supprimer tous les documents ayant le type `facture` : `{{:delete type="facture"}}`
* Supprimer tous les documents de type `devis` ayant une date dans le passé : `{{:delete :type="devis" where="$$.type = :type AND $$.date < datetime()"}}`

# Sections

## load <sup>(sql)</sup>

Note : cette section hérite de `sql` (voir plus haut). De ce fait, le nombre de résultats est limité à 10000 par défaut, si le paramètre `limit` n'est pas renseigné.

Charge un ou des documents pour le module courant.

| Paramètre | Optionnel / obligatoire ? | Fonction |
| :- | :- | :- |
| `module` | optionnel | Nom unique du module lié (par exemple : `recu_don`). Si non spécifié, alors le nom du module courant sera utilisé. |
| `key` | optionnel | Clé unique du document |
| `id` | optionnel | Numéro unique du document |
| `each` | optionnel | Traiter une clé du document comme un tableau |

Il est possible d'utiliser d'autres paramètres : `{{#load cle="valeur"}}`. Cela va comparer `"valeur"` avec la valeur de la clé `cle` dans le document JSON. C'est l'équivalent d'écrire `where="json_extract(document, '$.cle') = 'valeur'"`.

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
{{#load module="invoice" type="quote"}}
<h1>Titre du devis : {{$subject}}</h1>
<h2>Montant : {{$total}}</h2>
{{/load}}
```

### Utilisation du paramètre `each`

Le paramètre `each` est utile pour faire une boucle sur un tableau contenu dans le document. Ce paramètre doit contenir un chemin JSON valide. Par exemple `membres[1].noms` pour boucler sur le tableau `noms`, du premier élément du tableau `membres`. Voir la documentation [de la fonction json_each de SQLite pour plus de détails](https://www.sqlite.org/json1.html#jeach).

Pour chaque itération de la section, la variable `{{$value}}` contiendra l'élément recherché dans le critère `each`.

Par exemple nous pouvons avoir un élément `membres` dans notre document JSON qui contient un tableau de noms de membres :

```
{{:assign var="membres." value="Greta Thunberg}}
{{:assign var="membres." value="Valérie Masson-Delmotte"}}
{{:save membres=$membres}}
```

Nous pouvons utiliser `each` pour faire une liste :

```
{{#load each="membres"}}
- {{$value}}
{{/load}}
```

Ou pour récupérer les documents qui correspondent à un critère :

```
{{#load each="membres" where="value = 'Greta Thunberg'"}}
Le document n°{{$id}} est celui qui parle de Greta.
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
| `group` | *optionnel* | Expression SQL utilisée pour le groupement des résultats (`GROUP BY`). |
| `count` | *optionnel* | Expression SQL utilisée pour le décompte des résultats. Défaut : `COUNT(*)`. Principalement utile avec la clause `group`. |
| `desc` | *optionnel* | Si ce paramètre est à `true`, l'ordre de tri sera inversé. |
| `max` | *optionnel* | Nombre d'éléments à afficher sur chaque page. Mettre à `null` pour ne pas paginer la liste. |
| `where` | *optionnel* | Condition `WHERE` de la requête SQL. |
| `debug` | *optionnel* | Si ce paramètre existe, la requête SQL exécutée sera affichée avant le début de la boucle. |
| `explain` | *optionnel* | Si ce paramètre existe, l'explication de la requête SQL exécutée sera affichée avant le début de la boucle. | 
| `disable_user_sort` | *optionnel* | Booléen. Si ce paramètre est `true`, il ne sera pas possible à l'utilisateur d'ordonner les colonnes. |

Pour déterminer quelles colonnes afficher dans le tableau, il faut utiliser soit le paramètre `schema` pour indiquer un fichier de schéma JSON qui sera utilisé pour donner le libellé des colonnes (via la `description` indiquée dans le schéma), soit le paramètre `select`, où il faut alors indiquer le nom et le libellé des colonnes sous la forme `$$.colonne1 AS "Libellé"; $$.colonne2 AS "Libellé 2"`.

Comme pour `load`, il est possible d'utiliser des paramètres supplémentaires : `cle="valeur"`. Cela va comparer `"valeur"` avec la valeur de la clé `cle` dans le document JSON. C'est l'équivalent d'écrire `where="json_extract(document, '$.cle') = 'valeur'"`.

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

**Attention :** une seule liste peut être utilisée dans une même page. Avoir plusieurs listes provoquera des problèmes au niveau du tri des colonnes.

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
