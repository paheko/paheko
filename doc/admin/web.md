Title: Squelettes du site web dans Paheko

{{{.nav
* [Documentation Brindille](brindille.html)
* [Fonctions](brindille_functions.html)
* [Sections](brindille_sections.html)
* [Filtres](brindille_modifiers.html)
}}}

# Les squelettes du site web

Les squelettes sont un ensemble de fichiers qui permettent de modéliser l'apparence du site web selon ses préférences et besoins.

La syntaxe utilisée dans les squelettes s'appelle **[Brindille](brindille.html)**. Voir la [documentation de Brindille](brindille.html) pour son fonctionnement.

# Exemples de sites réalisés avec Paheko

* [ASBM Mortagne](https://asbm-mortagne.fr/)
* [Vélocité 63](https://www.velocite63.fr/)
* [La rustine, Dijon](https://larustine.org/)
* [Tauto école](https://tauto-ecole.net/) [(les squelettes sont disponibles ici)](https://gitlab.com/noizette/squelettes-garradin-tauto-ecole/)
* [La boîte à vélos](https://boiteavelos.chenove.net/)

# Fonctionnement des squelettes

Par défaut sont fournis plusieurs squelettes qui permettent d'avoir un site web basique mais fonctionnel : page d'accueil, menu avec les catégories de premier niveau, et pour afficher les pages, les catégories, les fichiers joints et images. Il y a également un squelette `atom.xml` permettant aux visiteurs d'accéder aux dernières pages publiées.

Les squelettes peuvent être modifiés via l'onglet **Configuration** de la section **Site web** du menu principal.

Une fois un squelette modifié, il apparaît dans la liste comme étant modifié, sinon il apparaît comme *défaut*. Si vous avez commis une erreur, il est possible de restaurer le squelette d'origine.

## Adresses des pages du site

Les squelettes sont appelés en fonction des règles suivantes (dans l'ordre) :

| Squelette appelé | Cas où le squelette est appelé |
| :---- | :---- |
| `adresse` | Si l'adresse `adresse` est appelée, et qu'un squelette du même nom existe |
| `adresse/index.html` | Si l'adresse `adresse/` est appelée, et qu'un squelette `index.html` dans le répertoire du même nom existe |
| `category.html` | Toute autre adresse se terminant par un slash `/`, si une catégorie du même nom existe |
| `article.html` | Toute autre adresse, si une page du même nom existe | 
| `404.html` | Si aucune règle précédente n'a fonctionné |

Ainsi l'adresse `https://monsite.paheko.cloud/Actualite/` appellera le squelette `category.html`, mais l'adresse `https://monsite.paheko.cloud/Actualite` (sans slash à la fin) appellera le squelette `article.html` si un article avec l'URI `Actualite` existe. Si un squelette `Actualite` (sans extension) existe, c'est lui qui sera appelé en priorité et ni `category.html` ni `article.html` ne seront appelés.

Autre exemple : `https://monsite.paheko.cloud/atom.xml` appellera le squelette `atom.xml` s'il existe.

Ceci vous permet de créer de nouvelles pages dynamiques sur le site, par exemple pour notre atelier vélo nous avons une page `https://larustine.org/velos` qui appelle le squelette `velos` (sans extension), qui va afficher la liste des vélos actuellement en stock dans notre hangar.

Le type de fichier étant déterminé selon l'extension (`.html, .css, etc.`) pour les fichiers traités par Brindille, un fichier sans extension sera considéré comme un fichier texte par le navigateur. Si on veut que le squelette `velos` (sans extension) s'affiche comme du HTML il faut forcer le type en mettant le code `{{:http type="text/html"}}` au début du squelette (première ligne).

## Fichier content.css

Ce fichier est particulier, car il définit le style du contenu des pages et des catégories.

Ainsi il est également utilisé quand vous éditez un contenu dans l'administration. Donc si vous souhaitez modifier le style d'un élément du texte, il vaux mieux modifier ce fichier, sinon le rendu sera différent entre l'administration et le site public.
