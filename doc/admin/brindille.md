# Référence de Brindille

* [Référence des fonctions](brindille_functions.html) `{{:fonction ...}}`
* [Référence des sections](brindille_sections.html) `{{#section ...}}`
* [Référence des filtres](brindille_modifiers.html) `$variable|filtre`

La syntaxe utilisée s'appelle **Brindille**. Si vous avez déjà fait de la programmation, elle ressemble à un mélange de Mustache, Smarty, Twig et PHP.

Son but est de permettre une grande flexibilité, sans avoir à utiliser un "vrai" langage de programmation, mais en s'en rapprochant suffisamment quand même.

## Syntaxe de base

### Affichage de variable

Une variable est affichée à l'aide de la syntaxe : `{{$date}}` affichera la valeur brute de la date par exemple : `2020-01-31 16:32:00`.

La variable peut être modifiée à l'aide de filtres de modification, qui sont ajoutés avec le symbole de la barre verticale (pipe `|`) : `{{$date|date_long}}` affichera une date au format long : `jeudi 7 mars 2021`.

Ces filtres peuvent accepter des paramètres, séparés par deux points `:`. Exemple : `{{$date|date:"%d/%m/%Y"}}` affichera `31/01/2020`.

Par défaut la variable sera recherchée dans le contexte actuel de la section, si elle n'est pas trouvée elle sera recherchée dans le contexte parent (section parente), etc. jusqu'à trouver la variable.

Il est possible de faire référence à une variable d'un contexte particulier avec la notation à points : `{{$article.date}}`.

Il existe deux variables de contexte spécifiques : `$_POST` et `$_GET` qui permettent d'accéder aux données envoyées dans un formulaire et dans les paramètres de la page.

Par défaut le filtre `escape` est appliqué à toutes les variables pour protéger les variables contre les injections de code HTML. Ce filtre est appliqué en dernier, après les autres filtres. Il est possible de contourner cet automatisme en rajoutant le filtre `escape` ou `raw` explicitement. `raw` désactive tout échappement, mais `escape` est utilisé pour changer l'ordre d'échappement. Exemple :

```
{{:assign text = "Coucou
ça va ?" }}
{{$text|escape|nl2br}}
```

Donnera bien `Coucou<br />ça va ?`. Sans indiquer le filtre `escape` le résultat serait `Coucou&lt;br /&gt;ça va ?`.

#### Ordre de recherche des variables

Par défaut les variables sont recherchées dans l'ordre inverse, c'est à dire que sont d'abord recherchées les variables avec le nom demandé dans la section courante. Si la variable n'existe pas dans la section courante, alors elle est recherchée dans la section parente, et ainsi de suite jusqu'à ce que la variable soit trouvée, où qu'il n'y ait plus de section parente.

Prenons cet exemple :

```
{{#articles uri="Actualite"}}
  <h1>{{$title}}</h1>
    {{#images parent=$path limit=1}}
      <img src="{{$thumb_url}}" alt="{{$title}}" />
    {{/images}}
{{/articles}}
```

Dans la section `articles`, `$title` est une variable de l'article, donc la variable est celle de l'article.

Dans la section `images`, les images n'ayant pas de titre, la variable sera celle de l'article de la section parente, alors que `$thumb_url` sera lié à l'image.

#### Conflit de noms de variables

Imaginons que nous voulions mettre un lien vers l'article sur l'image de l'exemple précédent :

```
{{#articles uri="Actualite"}}
  <h1>{{$title}}</h1>
    {{#images parent=$path limit=1}}
      <a href="{{$url}}"><img src="{{$thumb_url}}" alt="{{$title}}" /></a>
    {{/images}}
{{/articles}}
```

Problème, ici `$url` fera référence à l'URL de l'image elle-même, et non pas l'URL de l'article.

La solution est d'ajouter un point au début du nom de variable : `{{$.url}}`.

Un point au début d'un nom de variable signifie que la variable est recherchée à partir de la section précédente. Il est possible d'utiliser plusieurs points, chaque point correspond à un niveau à remonter. Ainsi `$.url` cherchera la variable dans la section parente (et ses sections parentes si elle n'existe pas, etc.). De même, `$..url` cherchera dans la section parente de la section parente.

### Conditions

Il est possible d'utiliser des conditions de type "si" (`if`), "sinon si" (`elseif`) et "sinon" (`else`). Celles-ci sont terminées par un block "fin si" (`/if`).

```
{{if $date|date:"%Y" > 2020}}
    La date est en 2020
{{elseif $article.status == 'draft'}}
    La page est un brouillon
{{else}}
    Autre chose.
{{/if}}
```

### Fonctions

Une fonction va répondre à certains paramètres et renvoyer un résultat ou réaliser une action. Un bloc de fonction commence par le signe deux points `:` :

```
{{:http code=404}}
```

Contrairement aux autres types de blocs, et comme pour les variables, il n'y a pas de bloc fermant (avec un slash `/`).

### Sections

Une section est une partie de la page qui sera répétée une fois, plusieurs fois, ou zéro fois, selon ses paramètres et le résultat (c'est une "boucle"). Une section commence par un bloc avec un signe hash (`#`) et se termine par un bloc avec un slash (`/`).

Un exemple simple avec une section qui n'aura qu'une seule répétition :

```
{{#categories uri=$_GET.uri}}
    <h1>{{$title}}</h1>
{{/categories}}
```

Il est possible d'utiliser une condition `{{else}}` avant la fin du bloc pour avoir du contenu alternatif si la section ne se répète pas (dans ce cas si aucune catégorie ne correspond au critère).

Un exemple de sous-section

```
{{#categories uri=$_GET.uri}}
    <h1>{{$title}}</h1>

    {{#articles parent=$path order="published DESC" limit="10"}}
        <h2><a href="{{$url}}">{{$title}}</a></h2>
        <p>{{$content|truncate:600:"..."}}</p>
    {{else}}
        <p>Aucun article trouvé.</p>
    {{/articles}}

{{/categories}}
```

Voir la référence des sections pour voir quelles sont les sections possibles et quel est leur comportement.

### Sections litérales

Pour qu'une partie du code ne soit pas interprété, pour éviter les conflits avec certaines syntaxes, il est possible d'utiliser un bloc `literal` :

```
{{literal}}
<script>
// Ceci ne sera pas interprété
function test (a) {{
}}
</script>
{{/literal}}
```

### Commentaires

Les commentaires sont figurés dans des blocs qui commencent et se terminent par une étoile (`*`) :

```
{{* Ceci est un commentaire
Il sera supprimé du résultat final
Il peut contenir du code qui ne sera pas interprété :
{{if $test}}
OK
{{/if}}
*}}
```

## Référence des variables définies par défaut

Ces variables sont définies tout le temps :

| Nom | Contenu |
| - | - |
| `$_GET` | Alias de la super-globale _GET de PHP. |
| `$_POST` | Alias de la super-globale _POST de PHP. |
| `$root_url` | Adresse racine du site web Paheko. |
| `$request_url` | Adresse de la page courante. |
| `$admin_url` | Adresse de la racine de l'administration Paheko. |
| `$visitor_lang` | Langue préférée du visiteur, sur 2 lettres (exemple : `fr`, `en`, etc.). |
| `$logged_user` | Informations sur le membre actuellement connecté dans l'administration (vide si non connecté). |
| `$config.org_name` | Nom de l'association |
| `$config.org_email` | Adresse e-mail de l'association |
| `$config.org_phone` | Numéro de téléphone de l'association |
| `$config.org_address` | Adresse postale de l'association |
| `$config.org_web` | Adresse du site web de l'association |
| `$config.files.logo` | Adresse du logo de l'association, si définit dans la personnalisation |
| `$config.files.favicon` | Adresse de l'icône de favoris de l'association, si définit dans la personnalisation |
