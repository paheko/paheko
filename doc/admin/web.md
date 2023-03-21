Title: Squelettes du site web dans Paheko

{{{.nav
* [Documentation Brindille](brindille.html)
* [Fonctions](brindille_functions.html)
* [Sections](brindille_sections.html)
* [Filtres](brindille_modifiers.html)
}}}

# Les squelettes du site web

Les squelettes sont un ensemble de fichiers (code *Brindille*, CSS, etc.) qui permettent de modéliser l'apparence du site web selon ses préférences et besoins.

La syntaxe utilisée dans les squelettes s'appelle **Brindille**.

## Exemples de sites réalisés avec Paheko

* [Faidherbe Alumni](https://www.alumni-faidherbe.fr/)
* [ASBM Mortagne](https://asbm-mortagne.fr/)
* [Vélocité 63](https://www.velocite63.fr/)
* [La rustine, Dijon](https://larustine.org/)
* [Tauto école](https://tauto-ecole.net/) [(les squelettes sont disponibles ici)](https://gitlab.com/noizette/squelettes-garradin-tauto-ecole/)
* [La boîte à vélos](https://boiteavelos.chenove.net/)
* [Jardin du bon pasteur](https://jardindubonpasteur.fr)

## Fonctionnement des squelettes

Par défaut sont fournis plusieurs squelettes qui permettent d'avoir un site web basique mais fonctionnel : page d'accueil, menu avec les catégories de premier niveau, et pour afficher les pages, les catégories, les fichiers joints et images. Il y a également un squelette `atom.xml` permettant aux visiteurs d'accéder aux dernières pages publiées.

Les squelettes peuvent être modifiés via l'onglet **Configuration** de la section **Site web** du menu principal.

Une fois un squelette modifié, il apparaît dans la liste comme étant modifié, sinon il apparaît comme *défaut*. Si vous avez commis une erreur, il est possible de restaurer le squelette d'origine.

Dans la gestion des squelettes, seuls les fichiers ayant une des extensions `tpl, btpl, html, htm, b, skel, xml` seront traités par Brindille. De même, les fichiers qui n'ont pas d'extension seront également traités par Brindille.

Les autres types de fichiers seront renvoyés sans traitement, comme des fichiers "bruts". En d'autres termes, il n'est pas possible de mettre du code *Brindille* dans des fichiers non-texte.

Ainsi, nous appelons ici *squelette* tout fichier situé dans l'onglet **Configuration**, mais seuls les fichiers traités par Brindille sont de "vrais" squelettes au sens code exécutable par *Brindille*. Les autres ne sont pas traités ni exécutés : ils ne peuvent pas contenir de code Brindille.

### Adresses des pages du site

Les squelettes sont appelés en fonction des règles suivantes (dans l'ordre) :

| Adresse | Squelette appelé |
| ---- | ---- |
| `/` (racine du site) | `index.html` |
| Toute autre adresse se terminant par un slash `/` | `category.html` |
| Toute autre adresse, si un article existe avec cette URI | `article.html` |
| Toute autre adresse, si un squelette du même nom existe | Squelettes du même nom |

Ainsi l'adresse `https://monsite.paheko.cloud/Actualite/` appellera le squelette `category.html`, mais l'adresse `https://monsite.paheko.cloud/Actualite` (sans slash à la fin) appellera le squelette `article.html` si un article avec l'URI `Actualite` existe. Sinon si un squelette `Actualite` (sans extension) existe, c'est lui qui sera appelé.

Autre exemple : `https://monsite.paheko.cloud/atom.xml` appellera le squelette `atom.xml` vu qu'il existe.

Ceci vous permet de créer de nouvelles pages dynamiques sur le site, par exemple pour notre atelier vélo nous avons une page `https://larustine.org/velos` qui appelle le squelette `velos` (sans extension), qui va afficher la liste des vélos actuellement en stock dans notre hangar.

Le type de fichier étant déterminé selon l'extension (`.html, .css, etc.`) pour les fichiers traités par Brindille, un fichier sans extension sera considéré comme un fichier texte par le navigateur. Si on veut que le squelette `velos` (sans extension) s'affiche comme du HTML il faut forcer le type en mettant le code `{{:http type="text/html"}}` au début du squelette (première ligne).

### Squelette content.css

Ce fichier est particulier, car il définit le style du contenu des pages et des catégories. Ainsi il est également utilisé quand vous éditez un contenu dans l'administration. Donc si vous souhaitez modifier le style d'un élément du texte, il vaux mieux modifier ce fichier, sinon le rendu sera différent entre l'administration et le site public.
