Title: Référence des filtres Brindille

{{{.nav
* [Documentation Brindille](brindille.html)
* [Fonctions](brindille_functions.html)
* [Sections](brindille_sections.html)
* **[Filtres](brindille_modifiers.html)**
}}}

<<toc aside>>

# Filtres PHP

Ces filtres viennent directement de PHP et utilisent donc les mêmes paramètres :

* strtolower
* strtoupper
* ucfirst
* ucwords
* htmlentities
* htmlspecialchars
* trim, ltrim, rtrim
* lcfirst
* md5
* sha1
* metaphone
* soundex
* str_split
* str_word_count
* strrev
* strlen
* strip_tags
* nl2br
* wordwrap
* strlen
* abs

# Filtres de texte

## truncate

Arguments :

* nombre : longueur en nombre de caractères (défaut = 80)
* texte : texte à placer à la fin (si tronqué) (défaut = …)
* booléen : coupure stricte, si `true` alors un mot pourra être coupé en deux, sinon le texte sera coupé au dernier mot complet

Tronque un texte à une longueur définie.

## excerpt

Produit un extrait d'un texte.

Supprime les tags HTML, tronque au nombre de caractères indiqué en second argument (si rien n'est indiqué, alors 600 est utilisé), et englobe dans un paragraphe `<p>...</p>`.

Équivalent de :

```
<p>{{$html|strip_tags|truncate:600|nl2br}}</p>
```

## protect_contact

Crée un lien protégé pour une adresse email, pour éviter que l'adresse ne soit recueillie par les robots spammeurs (empêche également le copier-coller et le lien ne fonctionnera pas avec javascript désactivé).

## escape

Échappe le contenu pour un usage dans un document HTML. Ce filtre est appliqué par défaut à tout ce qui est affiché (variables, etc.) sauf à utiliser le filtre `raw` (voir plus bas).

## xml_escape

Échappe le contenu pour un usage dans un document XML.

## raw

Passer ce filtre désactive la protection automatique contre le HTML (échappement) dans le texte. À utiliser en connaissance de cause avec les contenus qui contiennent du HTML et sont déjà filtrés !

```
{{"<b>Test"}} = &lt;b&gt;Test
{{"<b>Test"|raw}} = <b>Test
```

## args

Remplace des arguments dans le texte selon le schéma utilisé par [sprintf](https://www.php.net/sprintf).

```
{{"Il y a %d résultats dans la recherche sur le terme '%s'."|args:$results_count:$query}}
= Il y a 5 résultat dans la recherche sur le terme 'test'.
```

## count

Compte le nombre d'entrées dans un tableau.

```
{{$products|count}}
= 5
```

## cat

Concaténer un texte avec un autre.

```
{{"Tangerine"|cat:" Dream"}}
= Tangerine Dream
```

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

Il est aussi possible d'utiliser 

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

## size_in_bytes

Renvoie une taille en octets, Ko, Mo, ou Go à partir d'une taille en octets.

```
{{100|size_in_bytes}} = 100 o
{{1500|size_in_bytes}} = 1,50 Ko
{{1048576|size_in_bytes}} = 1 Mo
```

## typo

Pour le français.

Ajoute des espaces insécables (`&nbsp;`) devant ou derrière les ponctuations françaises (`« » ? ! :`).

## money

Formatte une valeur de monnaie (exprimée avec les cents inclus : `1502` = 15,02) pour l'affichage :

```
{{$amount|money}}
15,02
```

## money_currency

Formatte une valeur de monnaie en ajoutant la devise :

```
{{$amount|money_currency}}
15,02 €
```

## remove_leading_number

Supprime le numéro au début d'un titre.

Cela permet de définir un ordre spécifique aux pages et catégories dans les listes.

```
{{"03. Beau titre"|remove_leading_number}}
Beau titre
```

## extract_leading_number

Extrait le numéro du titre.

## check_email

Permet de vérifier la validité d'une adresse email. Cette fonction vérifie la syntaxe de l'adresse mais aussi que le nom de domaine indiqué possède bien un enregistrement de type MX.

Renvoie `true` si l'adresse est valide.

```
{{if !$_POST.email|check_email}}
<p class="alert">L'adresse e-mail indiquée est invalide.</p>
{{/if}}
```

## Filtres de date

* `date` : formatte une date selon un format spécifié (identique au [format utilisé par PHP](https://www.php.net/manual/fr/datetime.format.php)). Si aucun format n'est utilisé, le défaut sera `d/m/Y à H:i`. (en français)
* `strftime` : formatte une date selon un format spécifié, identique [au format utilisé par la fonction strftime de PHP](https://www.php.net/strftime). Un format doit obligatoirement être spécifié. En passant un code de langue en second paramètre, cette langue sera utilisée. Sont supportés le français (`fr`) et l'anglais (`en`). Le défaut est le français si aucune valeur n'est passée en second paramètre .
* `relative_date` : renvoie une date relative à la date du jour : `aujourd'hui`, `hier`, `demain`, ou sinon `mardi 2 janvier` (si la date est de l'année en cours) ou `2 janvier 2021` (si la date est d'une autre année). En spécifiant `true` en second paramètre, l'heure sera ajoutée au format `14h34`.
* `date_short` : date au format court : `d/m/Y`, en spécifiant `true` en second paramètre l'heure sera ajoutée : `à H\hi`.
* `date_long` : date au format long : `lundi 2 janvier 2021`. En spécifiant `true` en second paramètre l'heure sera ajoutée : `à 20h42`.
* `date_hour` : renvoie l'heure uniquement à partir d'une date : `20h00`. En passant `true` en second paramètre, les minutes seront omises si elles sont égales à zéro : `20h`.
* `atom_date` : formatte une date au format ATOM : `Y-m-d\TH:i:sP`

## Filtres de condition

Ces filtres renvoient `1` si la condition est remplie, ou `0` sinon. Ils peuvent être utilisés dans les conditions :

```
{{if $page.path|match:"/aide"}}Bienvenue dans l'aide !{{/if}}
```

* `match` renvoie `1` si le texte indiqué est trouvé (insensible à la casse)
* `regexp_match`, idem mais avec une expression régulière (de type `/Bonjour|revoir/i`)
