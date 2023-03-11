{{{.nav
* [Documentation Brindille](brindille.html)
* [Fonctions](brindille_functions.html)
* **[Sections](brindille_sections.html)**
* [Filtres](brindille_modifiers.html)
}}}

<<toc aside level=2>>

# Sections généralistes

## foreach

Permet d'itérer sur un tableau par exemple :

```
{{#foreach from=$variable key="key" item="value"}}
{{$key}} = {{$value}}
{{/foreach}}
```

## restrict

Permet de limiter (restreindre) une partie de la page aux membres qui sont connectés et/ou qui ont certains droits.

Deux paramètres optionnels peuvent être utilisés ensemble (il n'est pas possible d'utiliser seulement un des deux) :

| Paramètre | Fonction |
| - | - |
| `level` | Niveau d'accès : read, write, admin |
| `section` | Section où le niveau d'accès doit s'appliquer : users, accounting, web, documents, config |

```
{{#restrict}}
	Un membre est connecté, mais on ne sait pas avec quels droits.
{{else}}
	Aucun membre n'est connecté.
{{/restrict}}
```

```
{{#restrict section="users" level="admin"}}
	Un membre est connecté, et il a le droit d'administrer les membres.
{{else}}
	Aucun membre n'est connecté, ou un membre est connecté mais n'est pas administrateur des membres.
{{/if}}
```

```
{{#restrict block=true section="accounting" level="write"}}
{{/restrict}}
Si le membre n'est pas connecté ou n'a pas le droit de modifier la compta, il aura une page d'erreur.
```

# Sections SQL

Dans toutes les sections héritées de `sql` suivantes il est possible d'utiliser les paramètres suivants :

| Paramètre | Fonction |
| - | - |
| `where` | Condition de sélection des résultats |
| `begin` | Début des résultats, si vide une valeur de `0` sera utilisée. |
| `limit` | Limitation des résultats. Si vide, une valeur de `1000` sera utilisée. |
| `order` | Ordre de tri des résultats. Si vide le tri sera fait par ordre d'ajout dans la base de données. |
| `debug` | Si ce paramètre existe, la requête SQL exécutée sera affichée avant le début de la boucle. |

Il est également possible de passer des arguments dans les paramètres à l'aides des arguments nommés qui commencent par deux points `:` : `{{#articles where="title = :montitre" :montitre="Actualité"}}`

## sql

Effectue une requête SQL de type `SELECT` dans la base de données.

```
{{#sql select="*, julianday(date) AS day" tables="membres" where="id_categorie = :id_categorie" :id_categorie=$_GET.id_categorie order="numero DESC" begin=":page*100" limit=100 :page=$_GET.page}}
…
{{/sql}}
```

### Paramètres possibles

| Paramètre | Fonction |
| - | - |
| `tables` | Liste des tables à utiliser dans la requête. Ce paramètre est obligatoire. |
| `select` | Liste des colonnes à sélectionner, si non spécifié, toutes les colonnes (`*`) seront sélectionnées |
| `group` | Contenu de la clause `GROUP BY` |

## pages, articles, categories (héritent de sql)

* `pages` renvoie une liste de pages, qu'elles soient des articles ou des catégories
* `categories` ne renvoie que des catégories
* `articles` ne renvoie que des articles

À part cela les trois types de section se comportent de manière identique

### Paramètres possibles

| Paramètre | Fonction |
| - | - |
| `search` | Renseigner ce paramètre avec un terme à rechercher dans le texte ou le titre. Dans ce cas par défaut le tri des résultats se fait sur la pertinence, sauf si le paramètre `order` est spécifié. Dans ce cas une variable `$snippet` sera disponible à l'intérieur de la boucle, contenant les termes trouvés. |
| `future` | Renseigner ce paramètre à `false` pour que les articles dont la date est dans le futur n'apparaissent pas, `true` pour ne renvoyer QUE les articles dans le futur, et `null` (ou ne pas utiliser ce paramètre) pour que tous les articles, passés et futur, apparaissent. |
| `parent` | Indiquer ici le chemin d'article ou de catégorie parente. Utile pour renvoyer par exemple la liste des articles d'une catégorie. |

## files, documents, images (héritent de sql)

* `files` renvoie une liste de fichiers
* `documents` renvoie une liste de fichiers qui ne sont pas des images
* `images` renvoie une liste de fichiers qui sont des images

À part cela les trois types de section se comportent de manière identique

Note : seul les fichiers de la section site web sont accessibles, les fichiers de membres, de comptabilité, etc. ne sont pas disponibles.

### Paramètres possibles

| Paramètre | Fonction |
| - | - |
| `parent` (obligatoire) | Chemin (adresse unique) de l'article ou catégorie parente dont ont veut lister les fichiers |
| `except_in_text` | passer `true` à ce paramètre , et seuls les fichiers qui ne sont pas liés dans le texte de la page seront renvoyés |

## breadcrumbs

Permet de récupérer la liste des pages parentes d'une page afin de constituer un [fil d'ariane](https://fr.wikipedia.org/wiki/Fil_d'Ariane_(ergonomie)) permettant de remonter dans l'arborescence du site

Un seul paramètre est possible :

| Paramètre | Fonction |
| - | - |
| `path` (obligatoire) | Chemin (adresse unique) de la page parente |

Chaque itération renverra trois variables :

| Variable | Contenu |
| - | - |
| `$title` | Titre de la page ou catégorie |
| `$url` | Adresse HTTP de la page ou catégorie |
| `$path` | Chemin (adresse unique) de la page ou catégorie |

### Exemple

```
{{#breadcrumbs path=$page.path}}
&rarr; <a href="{{ $url }}">{{ $title }}</a><br />
{{/breadcrumbs}}
```
