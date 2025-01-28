Title: Référence des filtres Brindille

{{{.nav
* [Modules](modules.html)
* [Documentation Brindille](brindille.html)
* [Fonctions](brindille_functions.html)
* [Sections](brindille_sections.html)
* **[Filtres](brindille_modifiers.html)**
}}}

<<toc aside>>

# Filtres PHP

Ces filtres viennent directement de PHP et utilisent donc les mêmes paramètres. Voir la documentation PHP pour plus de détails.

| Nom | Description | Documentation PHP |
| :- | :- | :- |
| `htmlentities` | Convertit tous les caractères éligibles en entités HTML | [Documentation PHP](https://www.php.net/htmlentities) |
| `htmlspecialchars` | Convertit les caractères spéciaux en entités HTML | [Documentation PHP](https://www.php.net/htmlspecialchars) |
| `trim` | Supprime les espaces et lignes vides au début et à la fin d'un texte | [Documentation PHP](https://www.php.net/trim) |
| `ltrim` | Supprime les espaces et lignes vides au début d'un texte | [Documentation PHP](https://www.php.net/ltrim) |
| `rtrim` | Supprime les espaces et lignes vides à la fin d'un texte | [Documentation PHP](https://www.php.net/rtrim) |
| `md5` | Génère un hash MD5 d'un texte | [Documentation PHP](https://www.php.net/md5) |
| `sha1` | Génère un hash SHA1 d'un texte | [Documentation PHP](https://www.php.net/sha1) |
| `strlen` | Nombre de caractères dans une chaîne de texte | [Documentation PHP](https://www.php.net/strlen) |
| `strpos` | Position d'un élément dans une chaîne de texte | [Documentation PHP](https://www.php.net/strpos) |
| `strrpos` | Position d'un dernier élément dans une chaîne de texte | [Documentation PHP](https://www.php.net/strrpos) |
| `substr` | Découpe une chaîne de caractère | [Documentation PHP](https://www.php.net/substr) |
| `strtotime` | Transforme une date en timestamp UNIX | [Documentation PHP](https://www.php.net/strtotime) |
| `strip_tags` | Supprime les tags HTML | [Documentation PHP](https://www.php.net/strip_tags) |
| `nl2br` | Remplace les retours à la ligne par des tags HTML `<br/>` | [Documentation PHP](https://www.php.net/nl2br) |
| `wordwrap` | Ajoute des retours à la ligne tous les 75 caractères | [Documentation PHP](https://www.php.net/wordwrap) |
| `abs` | Renvoie la valeur absolue d'un nombre (exemple : -42 sera transformé en 42) | [Documentation PHP](https://www.php.net/abs) |
| `gettype` | Renvoie le type d'une variable | |
| `intval` | Transforme une valeur en entier (integer) | [Documentation PHP](https://www.php.net/intval) |
| `boolval` | Transforme une valeur en booléen (true ou false) | [Documentation PHP](https://www.php.net/boolval) |
| `floatval` | Transforme une valeur en nombre flottant (à virgule) | [Documentation PHP](https://www.php.net/floatval) |
| `strval` | Transforme une valeur en chaîne de texte | [Documentation PHP](https://www.php.net/strval) |
| `arrayval` | Transforme une valeur en tableau | [Documentation PHP](https://www.php.net/manual/fr/language.types.type-juggling.php) |
| `json_decode` | Transforme une chaîne JSON en valeur | [Documentation PHP](https://www.php.net/json_decode) |
| `json_encode` | Transforme une valeur en chaîne JSON | [Documentation PHP](https://www.php.net/json_encode) |
| `http_build_query` | Transformer un tableau en chaîne *query string* pour URL | [Documentation PHP](https://www.php.net/http_build_query) |
| `str_getcsv` | Transformer une chaîne de texte de format CSV en tableau | [Documentation PHP](https://www.php.net/str_getcsv) |

# Filtres utiles pour les e-mails

## check_email

Permet de vérifier la validité d'une adresse email. Cette fonction vérifie la syntaxe de l'adresse mais aussi que le nom de domaine indiqué possède bien un enregistrement de type MX.

Renvoie `true` si l'adresse est valide.

```
{{if !$_POST.email|check_email}}
<p class="alert">L'adresse e-mail indiquée est invalide.</p>
{{/if}}
```

## protect_contact

Crée un lien protégé pour une adresse email, pour éviter que l'adresse ne soit recueillie par les robots spammeurs (empêche également le copier-coller et le lien ne fonctionnera pas avec javascript désactivé).

# Filtres de tableaux

## array_to_list

Transforme un tableau en liste textuelle, par exemple pour envoyer dans un e-mail.

```
{{:assign var="table" a="blue" b="cyan"}}
{{:assign var="table.c" color1="darkred" color2="darkgreen"}}
{{$table|array_to_list}}
```

```
a = blue
b = cyan
c =
 color1 = darkred
 color2 = darkgreen
```

## has

Renvoie vrai si le tableau contient l'élément passé en paramètre.

```
{{:assign var="table" a="bleu" b="orange"}}
{{if $table|has:"bleu"}}
	Oui, il y a du bleu
{{/if}}
```

## in

Renvoie vrai si l'élément fait partie du tableau passé en paramètre.

C'est exactement la même chose que `has`, mais exprimé à l'envers.

```
{{:assign var="table" a="bleu" b="orange"}}
{{if "bleu"|in:$table}}
	Oui, il y a du bleu
{{/if}}
```

## has_key

Renvoie vrai si le tableau contient la clé passée en paramètre.

```
{{:assign var="table" a="bleu" b="orange"}}
{{if $table|has_key:"b"}}
	Oui, il y a la clé "b"
{{/if}}
```

## key_in

Renvoie vrai si la clé fait partie du tableau passé en paramètre.

C'est exactement la même chose que `has_key`, mais exprimé à l'envers.

```
{{:assign var="table" a="bleu" b="orange"}}
{{if "b"|key_in:$table}}
	Oui, il y a la clé "b"
{{/if}}
```

## keys

Renvoie les clés du tableau, sous forme de tableau.

```
{{:assign var="table" a="bleu" b="orange"}}
{{:assign var="cles" value=$table|keys}}
{{$cles|implode:","}}
```

Donnera :

```
a,b
```

## values

Renvoie les valeurs du tableau, sous forme de tableau.

Cela revient en fait à supprimer les clés associatives.

```
{{:assign var="table" a="bleu" b="orange"}}
{{#foreach from=$table key="cle" item="valeur"}}
	{{$cle}} = {{$valeur}}
{{/foreach}}
--
{{:assign var="valeurs" value=$table|values}}
{{#foreach from=$valeurs key="cle" item="valeur"}}
	{{$cle}} = {{$valeur}}
{{/foreach}}
```

Donnera :

```
a = bleu
b = orange
--
0 = bleu
1 = orange
```

## count

Compte le nombre d'entrées dans un tableau.

```
{{$products|count}}
= 5
```

## explode

Sépare une chaîne de texte en tableau à partir d'une chaîne de séparation.

```
{{:assign var="table" value="a,b,c"|explode:","}}
- {{$table.0}}
- {{$table.1}}
- {{$table.2}}
```

Affichera :

```
- a
- b
- c
```

## implode

Réunit un tableau sous forme de chaîne de texte en utilisant éventuellement une chaîne de liaison entre chaque élément du tableau.

```
{{:assign var="table" a="bleu" b="orange"}}
{{$table|implode}}
{{$table|implode:" - "}}
```

Affichera :

```
bleuorange
bleu - orange
```

## map

Applique un filtre sur chaque élément du tableau.

Le premier paramètre doit être le nom du filtre. Les autres paramètres seront passés au filtre.

```
{{:assign var="table" a="01" b="02"}}
{{:assign var="table" value=$table|map:intval}}
- {{$table.a}}
- {{$table.b}}
```

Affichera :

```
- 1
- 2
```

## ksort, sort

Trie un tableau par ordre alpha-numérique, sans tenir compte des majuscules/minuscules. `ksort` trie le tableau en utilisant les clés, et `sort` trie le tableau en utilisant les valeurs.

```
{{:assign var="table" b="3" a="2" c="1"}}
{{$table|sort|implode:","}}
{{$table|ksort|implode:","}}
```

Affichera :

```
1,2,3
2,3,1
```

## reverse

Inverse l'ordre des éléments d'un tableau.

```
{{:assign var="table" b="B" a="A" c="AC"}}
{{$table|reverse|implode:","}}
```

Affichera :

```
AC,A,B
```

## max, min

Renvoie respectivement la valeur la plus haute ou la plus basse d'un tableau de valeurs numériques.

```
{{:assign var="table" b="3" a="2" c="1"}}
{{$table|max}}
{{$table|min}}
```

Affichera :

```
3
1
```

# Filtres de texte

## args

Remplace des arguments dans le texte selon le schéma utilisé par [sprintf](https://www.php.net/sprintf).

```
{{"Il y a %d résultats dans la recherche sur le terme '%s'."|args:$results_count:$query}}
= Il y a 5 résultat dans la recherche sur le terme 'test'.
```

## cat

Concaténer un texte avec un autre.

```
{{"Tangerine"|cat:" Dream"}}
= Tangerine Dream
```

## count_words

Compte le nombre de mots dans un texte.

## escape

Échappe le contenu pour un usage dans un document HTML. Ce filtre est appliqué par défaut à tout ce qui est affiché (variables, etc.) sauf à utiliser le filtre `raw` (voir plus bas).

## excerpt

Produit un extrait d'un texte.

Supprime les tags HTML, tronque au nombre de caractères indiqué en second argument (si rien n'est indiqué, alors 600 est utilisé), et englobe dans un paragraphe `<p>...</p>`.

Équivalent de :

```
<p>{{$html|strip_tags|truncate:600|nl2br}}</p>
```

## extract_leading_number

Extrait le numéro au début d'une chaîne de texte.

Exemple :

```
{{:assign title="02. Cours sur la physique nucléaire"}}
{{$title|extract_leading_number}}
```

Affichera :

```
02
```

## format_phone_number

Formatte un numéro de téléphone selon le format du pays de l'association.

Seule la France est supportée pour le moment.

Exemple :

```
{{:assign number="0102030405"}}
{{$number|format_phone_number}}
```

Affichera :

```
01 02 03 04 05
```

## get_leading_number

Renvoie le numéro au début d'un titre.

```
{{"03. Beau titre"|get_leading_number}}
3
```

## markdown

Transforme un texte en HTML en utilisant la syntaxe Markdown.

Il est conseillé de rajouter le filtre `|raw` pour ne pas échapper le HTML produit, si on veut afficher le texte formatté dans une page HTML.

```
{{$texte|markdown|raw}}
```

## minify

Supprime les retours à la ligne et espaces inutiles dans le code CSS ou Javascript.

```
{{$code|minify:'js'}}
```

## raw

Passer ce filtre désactive la protection automatique contre le HTML (échappement) dans le texte. À utiliser en connaissance de cause avec les contenus qui contiennent du HTML et sont déjà filtrés !

```
{{"<b>Test"}} = &lt;b&gt;Test
{{"<b>Test"|raw}} = <b>Test
```


## replace

Remplace des parties du texte par une autre partie.

```
{{"Tata yoyo"|replace:"yoyo":"yaya"}}
= Tata yaya
```

## regexp_replace

Remplace des valeurs en utilisant une expression rationnelles (regexp) ([documentation PHP](https://www.php.net/manual/fr/regexp.introduction.php)).

```
{{"Tartagueule"|regexp_replace:"/ta/i":"tou"}}
= tourtougueule
```


## remove_leading_number

Supprime le numéro au début d'un titre.

Cela permet de définir un ordre spécifique aux pages et catégories dans les listes.

```
{{"03. Beau titre"|remove_leading_number}}
Beau titre
```


## truncate

Tronque un texte à une longueur définie.

| Argument | Fonction | Valeur par défaut (si omis) |
| :- | :- | :- |
| 1 | longueur en nombre de caractères | 80 |
| 2 | texte à placer à la fin (si tronqué) | … |
| 3 | coupure stricte, si `true` alors un mot pourra être coupé en deux, si `false` le texte sera coupé au dernier mot complet | `false` |

```
{{:assign texte="Ceci n'est pas un texte."}}
{{$texte|truncate:19:"(...)":true}}
{{$texte|truncate:19:"":false}}
```

Affichera :

```
Ceci n'est pas un (...)
Ceci n'est pas un t
```

## typo

Formatte un texte selon les règles typographiques françaises : ajoute des espaces insécables devant ou derrière les ponctuations françaises (`« » ? ! :`).

## url_encode

Encode une chaîne de texte pour utilisation dans une adresse URL (alias de `rawurlencode` en PHP).

## url_decode

Décode une chaîne de texte venant d'une URL (alias de `rawurldecode` en PHP).

## cdata_escape

Échappe le contenu pour un usage dans une balise `<![CDATA[…]]>` d'un document XML.

## xml_escape

Échappe le contenu pour un usage dans un document XML.

## Autres filtres de texte

Les filtres suivants modifient la casse (majuscule/minuscules) d'un texte et ne fonctionneront correctement que si l'extension `mbstring` est installée sur le serveur. Sinon les lettres accentuées ne seront pas modifiées.

Note : il est donc préférable d'utiliser la propriété CSS [`text-transform`](https://developer.mozilla.org/en-US/docs/Web/CSS/text-transform) pour modifier la casse si l'usage n'est que pour l'affichage, et non pas pour enregistrer les données.

* `tolower` : transforme un texte en minuscules
* `toupper` : transforme un texte en majuscules
* `ucfirst` : met la première lettre du texte en majuscule
* `ucwords` : met la première lettre de chaque mot en majuscule
* `lcfirst` : met la première lettre du texte en minuscule

# Filtres sur les sommes en devises

## money

Formatte une valeur de monnaie pour l'affichage.

Une valeur de monnaie doit **toujours** inclure les cents (exprimée sous forme d'entier). Ainsi `15,02` doit être exprimée sous la forme `1502`.

Paramètres optionnels :

1. `true` (défaut) pour ne rien afficher si la valeur est zéro, ou `false` pour afficher `0,00`
2. `true` pour afficher le signe `+` si le nombre est positif (`-` est toujours affiché si le nombre est négatif)

```
{{* 12 345,67 = 1234567 *}}
{{:assign amount=1234567}}
{{$amount|money}}
12 345,67
```

## money_currency

Comme `money` (même paramètres), formatte une valeur de monnaie (entier) pour affichage, mais en ajoutant la devise.

```
{{:assign amount=1502}}
{{$amount|money_currency}}
15,02 €
```

## money_html

Idem que `money`, mais pour l'affichage en HTML :

```
{{* 12 345,67 = 1234567 *}}
{{:assign amount=1234567}}
{{$amount|money_html}}
<span class="money">12&nbsp;345,67</span>
```

## money_currency_html

Idem que `money_currency`, mais pour l'affichage en HTML :

```
{{:assign amount=1502}}
{{$amount|money_currency_html}}
<span class="money">15,02&nbsp;€</span>
```

## money_raw

Formatte une valeur de monnaie (entier) de manière brute : les milliers n'auront pas de séparateur.

```
{{:assign amount=1234567}}
{{$amount|money_raw}}
12345,67
```

## money_int

Transforme un nombre à partir d'une chaîne de caractère (par exemple `12345,67`) en entier (`1234567`) pour stocker une valeur de monnaie.

```
{{:assign montant=$_POST.montant|trim|money_int}}
```

# Filtres SQL

## quote_sql

Protège une chaîne contre les attaques SQL, pour l'utilisation dans une condition.

**Note : il est FORTEMENT déconseillé d'intégrer directement des sources extérieures dans les requêtes SQL, il est préférable d'utiliser les paramètres dans la boucle `sql` et ses dérivées, comme ceci : `{{#sql select="id, nom" tables="users" where="lettre_infos = :lettre" :lettre=$_GET.lettre}}`.**

Exemple :

```
{{:assign nom=$_GET.nom|quote_sql}}
{{#sql select="id, nom" tables="users" where="nom = %s"|args:$nom}}
```

## quote_sql_identifier

La même chose que `quote_sql`, mais pour les identifiants (par exemple nom de table ou de colonne).

Exemple :

```
{{:assign colonne=$_GET.colonne|quote_sql_identifier}}
{{#sql select="id, %s"|args:$colonne tables="users"}}
```

Il est possible d'utiliser un préfixe en argument, utile par exemple quand on a plusieurs tables avec le même nom de colonne :

```
{{:assign colonne=$_GET.colonne|quote_sql_identifier:"u1"}}
{{#sql select="u1.id, %s"|args:$colonne tables="users AS u1 INNER JOIN users AS u2 ON u2.id_parent = u1.id"}}
```

## sql_where

Permet de créer une partie d'une clause SQL `WHERE` complexe.

Le premier paramètre est le nom de la colonne (sans préfixe).

Paramètres :

1. Comparateur : `=, !=, IN, NOT IN, >, >=, <, <=`
2. Valeur à comparer (peut être un tableau)

Exemple pour afficher la liste des membres des catégories n°1 et n°2:

```
{{:assign var="list." value=1}}
{{:assign var="list." value=2}}
{{#sql select="nom" tables="users" where="id_category"|sql_where:'IN':$id_list}}
    {{$nom}}
{{/sql}}
```

Le requête SQL générée sera alors `SELECT nom FROM users WHERE id_category IN (1, 2)`.

## sql_user_fields

Permet de récupérer le contenu de champs de la fiche utilisateur pour une requête SQL.

C'est particulièrement utile si le module permet de sélectionner dans sa configuration une liste de champs de membre (par exemple pour la carte de membre, ou les reçus fiscaux).

Si un champ mentionné n'existe plus dans les fiches de membres, il sera ignoré.

* Le premier paramètre est la liste des champs (tableau ou chaîne de texte)
* Le second est le préfixe à utiliser (alias de la table membres), optionnel
* Le troisième est la chaîne de texte à utiliser pour coller les champs entre eux

Exemple :

```
{{:assign var="champs_adresse." value="rue"}}
{{:assign var="champs_adresse." value="ville"}}
{{:assign var="champs_adresse_sql" value=$champs_adresse|sql_user_fields:"u":" - "}}
{{#select !champs_adresse_sql AS adresse FROM users AS u; !champs_adresse_sql=$champs_adresse_sql}}
	{{$adresse}}
{{/select}}
```

Affichera :

```
30 rue de Machin Chose — Dijon
```

Et la clause SQL générée sera :

```
LTRIM(COALESCE(' - ' || u.rue, '') || COALESCE(' - ' || u.ville), ' - ')
```

# Filtres de date

## date

Formatte une date selon le format spécifié en premier paramètre.

Le format est identique au [format utilisé par PHP](https://www.php.net/manual/fr/datetime.format.php).

Si aucun format n'est indiqué, le défaut sera `d/m/Y à H:i`. (en français)

Exemples :

```
{{:assign this_year=$now|date:'Y'}}
{{$date|date:'d/m/Y'}}
```

## strftime

Formatte une date selon un format spécifié en premier paramètre.

Le format à utiliser est identique [au format utilisé par la fonction strftime de PHP](https://www.php.net/strftime).

Un format doit obligatoirement être spécifié.

En passant un code de langue en second paramètre, cette langue sera utilisée. Sont supportés le français (`fr`) et l'anglais (`en`). Le défaut est le français si aucune valeur n'est passée en second paramètre .

```
{{:assign this_year=$now|date:'%Y'}}
{{$date|date:'%d/%m/%Y'}}
```

## relative_date 

Renvoie une date relative à la date du jour : `aujourd'hui`, `hier`, `demain`, ou sinon `mardi 2 janvier` (si la date est de l'année en cours) ou `2 janvier 2021` (si la date est d'une autre année).

En spécifiant `true` en premier paramètre, l'heure sera ajoutée au format `14h34`.

## date_short

Formatte une date au format court : `d/m/Y`.

En spécifiant `true` en premier paramètre l'heure sera ajoutée : `à H\hi`.

## date_long

Formatte une date au format long : `lundi 2 janvier 2021`.

En spécifiant `true` en premier paramètre l'heure sera ajoutée : `à 20h42`.

## date_hour

Formatte une date en renvoyant l'heure uniquement : `20h00`.

En passant `true` en premier paramètre, les minutes seront omises si elles sont égales à zéro : `20h`.

## atom_date

Formatte une date au format ATOM : `Y-m-d\TH:i:sP`

## parse_date

Vérifie le format d'une chaîne de texte représentant la date et la transforme en chaîne de date standardisée au format `AAAA-MM-JJ`.

Les formats acceptés sont :

* `AAAA-MM-JJ`
* `JJ/MM/AAAA`
* `JJ/MM/AA`

## parse_datetime

Vérifie le format d'une chaîne de texte représentant la date et l'heure et la transforme en chaîne de date et heure standardisée au format `AAAA-MM-JJ HH:mm`.

Les formats acceptés sont :

* `AAAA-MM-JJ HH:mm:ss`
* `AAAA-MM-JJ HH:mm`
* `JJ/MM/AAAA HH:mm`

## parse_time

Vérifie le format d'une chaîne de texte représentant l'heure et la transforme en chaîne de date standardisée au format `HH:MM`.

Les formats acceptés sont :

* `HH:MM`
* `H:M`
* `H:MM`
* `HH:M`

Le séparateur peut être `:` ou `h`.

# Filtres de condition

Ces filtres sont à utiliser dans les conditions

## match

Renvoie `true` si le texte indiqué en premier paramètre est trouvé dans la variable.

Ce filtre est insensible à la casse.

```
{{if $page.path|match:"/aide"}}Bienvenue dans l'aide !{{/if}}
```

## regexp_match

Renvoie `true` si l'expression régulière indiquée en premier paramètre est trouvée dans la variable.

Exemple pour voir si le texte contient les mots "Bonjour" ou "Au revoir" (insensible à la casse) :

```
{{if $texte|regexp_match:"/Bonjour|Au revoir/i"}}
	Trouvé !
{{else}}
	Rien trouvé :-(
{{/if}}
```

# Autres filtres

## get_country_name

Renvoie le nom d'un pays à partir de son code ISO-3166.

```
{{:assign code="FR"}}
{{$code|get_country_name}}
```

Donnera : `France`

## math

Réalise un calcul mathématique. Cette fonction accepte :

* les nombres: `42`, `13,37`, `14.05`
* les signes : `+ - / *` pour additionner, diminuer, diviser ou multiplier
* les parenthèses : `( )`
* les fonctions : `round(0.5452, 2)` `ceil(29,09)` `floor(0.99)` mais aussi : min, max, cos, sin, tan, asin, acos, atan, sinh, cosh, tanh, exp, sqrt, abs, log, log10, et pi.

Le résultat est renvoyé sous la forme d'un entier, ou d'un nombre flottant dont les décimales sont séparées par un point.

```
{{"1+1"|math}}
= 2
```

Il est possible de donner d'autres arguments, de la même manière qu'avec `args` pour y inclure des données provenant de variables :

```
{{:assign age=42}}
{{"1+%d"|math:$age}}
= 43
{{:assign prix=39.99 tva=19.1}}
{{"round(%f*%f, 2)"|math:$prix:$tva}}
= 47.63
```

## or

Si la variable passée est évalue comme `false` (c'est à dire que sa valeur est un texte vide, ou un nombre qui vaut zéro, ou la valeur `false`), alors le premier paramètre sera utilisé.

```
{{:assign texte=""}}
{{$texte|or:"Le texte est vide"}}
```

Il est possible de chaîner les appels à `or` :

```
{{:assign texte1="" texte2="0"}}
{{$texte1|or:$texte2|or:"Aucun texte"}}
```

## size_in_bytes

Renvoie une taille en octets, Ko, Mo, ou Go à partir d'une taille en octets.

```
{{100|size_in_bytes}} = 100 o
{{1500|size_in_bytes}} = 1,50 Ko
{{1048576|size_in_bytes}} = 1 Mo
```

## spell_out_number

Épelle un nombre en toutes lettres.

Le premier paramètre peut être utilisé pour spécifier le code de la langue à utiliser (par défaut c'est le français, donc le code `fr`).

```
{{42|spell_out_number}}
```

Donnera :

```
quarante deux
```

## uuid

Renvoie un identifiant unique au format UUIDv4.
