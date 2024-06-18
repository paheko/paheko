Title: Référence complète MarkDown — Paheko

{{{.nav
* [Raccourcis claviers](keyboard.html)
* **[Syntaxe MarkDown complète](markdown.html)**
* [Référence rapide MarkDown](markdown_quickref.html)
}}}

<<toc aside>>

# Syntaxe MarkDown

Paheko permet d'utiliser la syntaxe [MarkDown](https://fr.wikipedia.org/wiki/Markdown) dans les pages du site web.

Cette syntaxe est la plus répandue dans les outils d'édition de texte, si vous ne la connaissez pas encore, voici les règles qu'on peut utiliser pour formatter du texte avec MarkDown dans la plupart des outils (dont Paheko), ainsi que [les règles spécifiques supportées par Paheko](#extensions).

## Styles de texte

### Italique

Pour mettre un texte en italique il faut l'entourer de tirets bas ou d'astérisques :

```
Ce texte est en *italique, dingue !*
```

Donnera :

> Ce texte est en *italique, dingue !*

### Gras

Pour le gras, procéder de la même manière, mais avec deux tirets bas ou deux astérisques :

```
Ce texte est **très gras**.
```

> Ce texte est **très gras**.

### Gras et italique

Pour combiner, utiliser trois tirets ou trois astérisques :

```
Ce texte est ***gras et italique***.
```

> Ce texte est ***gras et italique***.

### Barré

Utiliser un symbole tilde pour barrer un texte :

```
Texte ~~complètement barré~~.
```

> Texte ~~complètement barré~~.

### Surligné

Il est possible de marquer une phrase ou un mot comme surligné en l'entourant de deux signes égal :

```
Ce texte est ==surligné==.
```

> Ce texte est ==surligné==.


### Code

Il est possible d'indiquer du code dans une ligne de texte avec un caractère *backtick* (accent grave en français, obtenu avec les touches [Alt Gr + 7](https://superuser.com/questions/254076/how-do-i-type-the-tick-and-backtick-characters-on-windows)) :

```
Le code `<html>` c'est rigolo !
```

> Le code `<html>` c'est rigolo !

### Avertissement sur les styles de texte

Un style de texte ne s'applique que dans un même paragraphe, il n'est pas possible d'appliquer un style sur plusieurs paragraphes :

Dans l'exemple suivant, les astérisques ne seront pas remplacées par du gras, elles resteront telles quelles :

```
Ce texte n'est pas très **gras.

Et celui-ci encore moins**.
```

> Ce texte n'est pas très **gras.
> 
> Et celui-ci encore moins**.

## Liens

Créez un lien en mettant le texte désiré entre crochets et le lien associé entre parenthèses :

```
Je connais un super gestionnaire [d'association](https://paheko.cloud/) !
```

Donne :

> Je connais un super gestionnaire [d'association](https://paheko.cloud/) !

Il est possible de faire un lien vers une autre page du site web en utilisant son adresse unique : 

```
N'oubliez pas de [vous inscrire à notre atelier](atelier-soudure).
```

Il est aussi possible de simplement inclure une adresse URL et elle sera automatiquement transformée en lien :

```
https://paheko.cloud/
```

## Blocs

### Paragraphes et retours à la ligne

Une ligne vide indique un changement de paragraphe :

```
Ceci est un paragraphe.

Ceci est est un autre.
```

Un retour à la ligne simple est traité comme tel :

```
Ceci est un
paragraphe.
```

> Ceci est un
> paragraphe.

### Titres et sous-titres

Pour faire un titre, vous devez mettre un ou plusieurs caractères *hash* (`#`) au début de la ligne.

Un titre avec un seul caractère est un titre principal (niveau 1), avec deux caractères c'est un sous-titre (niveau 2), etc. jusqu'au niveau 6.

```
# Titre principal (niveau 1)
## Sous-titre (niveau 2)
### Sous-sous-titre (niveau 3)
#### Niveau 4
##### Niveau 5
###### Dernier niveau de sous-titre (6)
```

Donnera :

> # Titre principal (niveau 1) {.no_toc}
> ## Sous-titre (niveau 2) {.no_toc}
> ### Sous-sous-titre (niveau 3) {.no_toc}
> #### Niveau 4 {.no_toc}
> ##### Niveau 5 {.no_toc}
> ###### Dernier niveau de sous-titre (6) {.no_toc}


### Listes

Vous pouvez créer des listes avec les caractères astérisque (`*`) et tiret `-` en début de ligne pour des listes non ordonnées :

```
* une élément
* un autre
  - un sous élément
  - un autre sous élément
* un dernier élément
```

> * une élément
> * un autre
>   - un sous élément
>   - un autre sous élément
> * un dernier élément

Ou avec des nombres pour des listes ordonnées :

```
1. élément un
2. élément deux
```

> 1. élément un
> 2. élément deux

L'ordre des nombres n'est pas important, seul le premier nombre est utilisé pour déterminer à quel numéro commencer la liste.

Exemple :

```
3. A
5. B 
4. C
```

> 3. A
> 5. B 
> 4. C

Il est ainsi possible d'utiliser uniquement le même numéro pour ne pas avoir à numéroter sa liste :

```
1. Un
1. Deux
```

> 1. Un
> 1. Deux

### Citations

Les citations se font en ajoutant le signe *supérieur à* (`>`) au début de la ligne :

```
> Programming is not a science. Programming is a craft.
```

> Programming is not a science. Programming is a craft.

### Code

Créez un bloc de code en indentant chaque ligne avec quatre espaces, ou en mettant trois accents graves ``` ` ``` (*backtick*, obtenu avec [Alt Gr + 7](https://superuser.com/questions/254076/how-do-i-type-the-tick-and-backtick-characters-on-windows)) sur la ligne au dessus et en dessous de votre code:

	```
	<html>...</html>
	```

Résultat :

```
<html>...</html>
```

### Tableaux

Pour créer un tableau vous devez séparer les colonnes avec des barres verticales (`|`, obtenu avec les touches [AltGr + 6](https://fr.wikipedia.org/wiki/Barre_verticale#Saisie)).

La première ligne contient les noms des colonnes, la seconde ligne contient la ligne de séparation (chaque cellule doit contenir un ou plusieurs tirets), et les lignes suivantes représentent le contenu du tableau.

```
| Colonne 1 | Colonne 2 |
| - | - | - |
| AB | CD |
```

| Colonne 1 | Colonne 2 |
| - | - |
| AB | CD |

Par défaut les colonnes sont centrées. On peut aussi aligner le texte à gauche ou à droite en mettant deux points après le ou les tirets de la ligne suivant l'entête :

```
| Aligné à gauche  | Centré          | Aligné à droite |
| :--------------- |:---------------:| :--------------:|
| Aligné à gauche  | ce texte        | Aligné à droite |
| Aligné à gauche  | est             | Aligné à droite |
| Aligné à gauche  | centré          | Aligné à droite |
```

| Aligné à gauche  | Centré          | Aligné à droite |
| :--------------- |:---------------:| :--------------:|
| Aligné à gauche  | ce texte        | Aligné à droite |
| Aligné à gauche  | est             | Aligné à droite |
| Aligné à gauche  | centré          | Aligné à droite |

### Ligne de séparation

Il suffit de mettre au moins 3 tirets à la suite sur une ligne séparée pour ajouter une ligne de séparation :

```
---
```

Résultat :

---

### Commentaires

Pour ajouter un commentaire qui ne sera pas affiché dans le texte, utiliser la syntaxe suivante :

```
<!-- Ceci est un commentaire -->
```

## Notes de bas de page

Pour créer une note de base de page, il faut mettre entre crochets un signe circonflexe (obtenu en appuyant sur la touche circonflexe, puis sur espace) suivi du numéro ou du nom de la note. Enfin, à la fin du texte il faudra répéter les crochets, le signe circonflexe, suivi de deux points et de la définition.

```
Texte très intéressant[^1]. Approuvé par 100% des utilisateurs[^Source].

[^1]: Ceci est une note de bas de page
[^Source]: Enquête Paheko sur la base de 1 personne interrogée.
```

Donnera ceci :

> Texte très intéressant[^1]. Approuvé par 100% des utilisateurs[^Source].
>  
> [^1]: Ceci est une note de bas de page
> [^Source]: Enquête Paheko sur la base de 1 personne interrogée.


## Insertion de vidéos depuis un service de vidéo

Certains services vidéo comme les instances Peertube permettent l'intégration des vidéos.

Pour cela il faut recopier le code d'intégration donné par le service vidéo. Voici un exemple :

```
<iframe title="ENQUÊTE : Brûler la Forêt pour Sauver le Climat ? | EP 3 - Le bois énergie" width="560" height="315" src="https://peertube.stream/videos/embed/12c52265-e3b3-4bad-93f3-f2c1df5bbe4f" frameborder="0" allowfullscreen="" sandbox="allow-same-origin allow-scripts allow-popups"></iframe>
```

Résultat :

<iframe title="ENQUÊTE : Brûler la Forêt pour Sauver le Climat ? | EP 3 - Le bois énergie" width="560" height="315" src="https://peertube.stream/videos/embed/12c52265-e3b3-4bad-93f3-f2c1df5bbe4f" frameborder="0" allowfullscreen="" sandbox="allow-same-origin allow-scripts allow-popups"></iframe>

## Identifiant et classe CSS sur les titres

Il est possible de spécifier l'ID et la classe CSS d'un titre en les rajoutant à la fin du titre, entre accolades, comme ceci :

```
## Titre de niveau 2 {#titre2} {.text-center}
```

Le code HTML résultant sera comme ceci :

```
<h2 id="titre2" class="text-center">Titre de niveau 2</h2>
```

## Classes CSS

Il est possible de donner une classe CSS parente à un ensemble d'éléments en les mettant au centre d'un bloc définissant cette classe :

```
{{{.custom-quote .custom-block

Paragraphe

> Citation
}}}
```

Créera le code HTML suivant :

```
<div class="custom-quote custom-block">

	<p>Paragraphe</p>

	<blockquote><p>Citation</p></blockquote>
</div>
```

## Tags HTML

Certains tags HTML sont autorisés :

| Tag | Utilisation | Exemple |
| :- | :- | :- |
| `<kbd>` | Touches de clavier | <kbd>Ctrl</kbd> + <kbd>B</kbd> |
| `<samp>` | Exemple de programme en console | <samp>bohwaz@platypus ~ % sudo apt install paheko</samp> |
| `<var>` | Variable dans un programme informatique | <var>ab</var> + <var>cd</var> = 42 |
| `<del>` | Texte supprimé | Texte <del>supprimé</del> |
| `<ins>` | Texte ajouté | Texte <ins>ajouté</ins> |
| `<sup>` | Texte en exposant | Texte<sup>exposant</sup> |
| `<sub>` | Texte en indice | Texte<sub>indice</sub> |
| `<mark>` | Texte surligné | Texte <mark>surligné</mark> |
| `<audio>` | Insérer un lecteur audio dans la page | `<audio src="mon_fichier.mp3">` |
| `<video>` | Insérer une vidéo dans la page | `<video src="mon_fichier.webm">` |

Mais leurs possibilités sont limitées, notamment sur les attributs autorisés.

# Extensions

Paheko propose des extensions au langage MarkDown, qui n'existent pas dans les autres logiciels utilisant aussi MarkDown.

Toutes ces extensions se présentent sous la forme d'un code situé entre deux signes **inférieur à** (`<<`) et deux signes **supérieur à** (`>>`), à ne pas confondre avec les guillements français (`«` et `»`).

## Images jointes

Il est possible d'intégrer une image jointe à la page web en plaçant le code suivant sur une ligne (sans autre texte) :

```
<<image|Nom_fichier.jpg|Alignement|Légende>>
```

* `Nom_fichier.jpg` : remplacer par le nom du fichier de l'image (parmi les images jointes à la page)
* `Alignement` : remplacer par l'alignement :
  * `gauche` ou `left` : l'image sera placée à gauche en petit (200 pixels), le texte remplira l'espace laissé sur la droite de l'image ;
  * `droite` ou `right` : l'image sera placée à droite en petit, le texte remplira l'espace laissé sur la gauche de l'image ;
  * `centre` ou `center` : l'image sera placée au centre en taille moyenne (500 pixels), le texte sera placé au dessus et en dessous.
* Légende : indiquer ici une courte description de l'image.

Exemple :

```
<<image|mon_image.png|center|Ceci est une belle image>>
```

Il est aussi possible d'utiliser la syntaxe avec des paramètres nommés :

```
<<image file="Nom_fichier.jpg" align="center" caption="Légende">>
```

Les images qui ne sont pas mentionnées dans le texte seront affichées après le texte sous forme de galerie.

Cette extension ne fonctionne que dans les pages du site web.

## Galerie d'images

Il est possible d'afficher une galerie d'images (sous forme d'images miniatures) avec la balise `<<gallery` qui contient la liste des images à mettre dans la galerie :

```
<<gallery
Nom_fichier.jpg
Nom_fichier_2.jpg
>>
```

Si aucun nom de fichier n'est indiqué, alors toutes les images jointes à la page seront affichées :

```
<<gallery>>
```

Cette extension ne fonctionne que dans les pages du site web.

### Diaporama d'images

On peut également afficher cette galerie sous forme de diaporama. Dans ce cas une seule image est affichée, et on peut passer de l'une à l'autre.

La syntaxe est la même, mais on ajoute le mot `slideshow` après le mot `gallery` :

```
<<gallery slideshow
Nom_fichier.jpg
Nom_fichier_2.jpg
>>
```

Cette extension ne fonctionne que dans les pages du site web.

## Fichiers joints

Pour créer un bouton permettant de voir ou télécharger un fichier joint à la page web, il suffit d'utiliser la syntaxe suivante :

```
<<file|Nom_fichier.ext|Libellé>>
```

* `Nom_fichier.ext` : remplacer par le nom du fichier  (parmi les fichiers joints à la page)
* `Libellé` : indique le libellé du qui sera affiché sur le bouton, si aucun libellé n'est indiqué alors c'est le nom du fichier qui sera affiché

Cette extension ne fonctionne que dans les pages du site web.

## Vidéos

Pour inclure un lecteur vidéo dans la page web à partir d'un fichier vidéo joint à la page, il faut utiliser le code suivant :

```
<<video|Nom_du_fichier.ext>>
```

On peut aussi spécifier d'autres paramètres :

* `file` : nom du fichier vidéo
* `poster` : nom de fichier d'une image utilisée pour remplacer la vidéo avant qu'elle ne soit lue
* `subtitles` : nom d'un fichier de sous-titres au format VTT ou SRT
* `width` : largeur de la vidéo (en pixels)
* `height` : hauteur de la vidéo (en pixels)

Exemple :

```
<<video file="Ma_video.webm" poster="Ma_video_poster.jpg" width="640" height="360" subtitles="Ma_video_sous_titres.vtt">>
```

Cette extension ne fonctionne que dans les pages du site web.

## Sommaire / table des matières automatique

Il est possible de placer le code `<<toc>>` pour générer un sommaire automatiquement à partir des titres et sous-titres :

```
<<toc>>
```

Affichera un sommaire comme celui-ci :

<<toc>>

Il est possible de limiter les niveaux en utilisant le paramètre `level` comme ceci :

```
<<toc level=1>>
```

N'affichera que les titres de niveau 1 (précédés d'un seul signe hash `#`), comme ceci :

<<toc level=1>>

Enfin il est possible de placer la table des matières sur le côté du texte, en utilisant le paramètre `aside` :

```
<<toc level=1 aside>>
```

Note : en plus de la syntaxe `<<toc>>`, Paheko supporte aussi les syntaxes suivantes par compatibilité avec [les autres moteurs de rendu MarkDown](https://alexharv074.github.io/2018/08/28/auto-generating-markdown-tables-of-contents.html) : `{:toc}` `[[_TOC_]]` `[toc]`.

### Exclure un sous-titre du sommaire

Il est aussi possible d'indiquer qu'un titre ne doit pas être inclus dans le sommaire en utilisant la classe `no_toc` comme ceci :

```
## Sous-titre non-inclus {.no_toc}
```

## Grilles et colonnes

Pour une mise en page plus avancée, il est possible d'utiliser les *grilles*, adaptation des [grids en CSS](https://developer.mozilla.org/fr/docs/Web/CSS/CSS_Grid_Layout). Il faut utiliser la syntaxe `<<grid>>...Contenu...<</grid>>`.

Attention, les blocs `<<grid>>` et `<</grid>>` doivent obligatoirement être placés sur des lignes qui ne contiennent rien d'autre.

**Note :** sur petit écran (mobile ou tablette) les grilles et colonnes sont désactivées, tout sera affiché dans une seule colonne, comme si les grilles n'étaient pas utilisées.

Pour spécifier le nombre de colonnes on peut utiliser un raccourci qui *mime* les colonnes, comme ceci :

```
<<grid !!>>
```

Ce code indique qu'on veut créer une grille de 2 colonnes de largeur identique.

Dans les raccourcis, le point d'exclamation `!` indique une colonne simple, et le hash `#` indique une colonne qui prend le reste de la place selon le nombre de colonnes total.

D'autres exemples de raccourcis :

* `!!` : deux colonnes de largeur égale
* `!!!` : trois colonnes de largeur égale
* `!##` : deux colonnes, la première occupant un tiers de la largeur, la seconde occupant les deux tiers
* `!##!` : 4 colonnes, la première occupant un quart de la largeur, la seconde occupant la moitié, la dernière occupant le quart

Alternativement, pour plus de contrôle, ce bloc accepte les paramètres suivants :

* `short` : notation courte décrite ci-dessus
* `gap` : espacement entre les blocs de la grille
* `template` : description CSS complète de la grille (propriété [`grid-template`](https://developer.mozilla.org/fr/docs/Web/CSS/grid-template))

Après ce premier bloc `<<grid>>` qui définit la forme de la grille, on peut entrer le contenu de la première colonne.

Pour créer la seconde colonne il faut simplement placer un nouveau bloc `<<grid>>` vide (aucun paramètre) sur une ligne.

Enfin on termine en fermant la grille avec un block `<</grid>>`. Voici un exemple complet :

```
<<grid !!!>>
Col. 1
<<grid>>
Col. 2
<<grid>>
Col. 3
<</grid>>
```

<<grid short="!!!" debug>>
Col. 1
<<grid>>
Col. 2
<<grid>>
Col. 3
<</grid>>

Exemple avec 3 colonnes, dont 2 petites et une large :

```
<<grid !##!>>
Col. 1
<<grid>>
Colonne 2 large
<<grid>>
Col. 3
<</grid>>
```

<<grid short="!##!" debug>>
Col. 1
<<grid>>
Colonne 2 large
<<grid>>
Col. 3
<</grid>>

Il est possible de créer plus de blocs qu'il n'y a de colonnes, cela créera une nouvelle ligne avec le même motif :

```
<<grid !!>>
L1 C1
<<grid>>
L1 C2
<<grid>>
L2 C1
<<grid>>
L2 C2
<</grid>>
```

<<grid short="!!" debug>>
L1 C1
<<grid>>
L1 C2
<<grid>>
L2 C1
<<grid>>
L2 C2
<</grid>>

Enfin, il est possible d'utiliser la notation CSS [`grid-row`](https://developer.mozilla.org/en-US/docs/Web/CSS/grid-row) et [`grid-column`](https://developer.mozilla.org/en-US/docs/Web/CSS/grid-column) pour chaque bloc, permettant de déplacer les blocs, ou de faire en sorte qu'un bloc s'étende sur plusieurs colonnes ou plusieurs lignes. Pour cela il faut utiliser le paramètre `row` ou `column` qui précède le bloc :

```
<<grid short="#!!" column="span 2">>
A
<<grid row="span 2">>
B
<<grid>>
C
<<grid>>
D
<</grid>>
```

<<grid short="#!!" debug column="span 2">>
A
<<grid row="span 2">>
B
<<grid>>
C
<<grid>>
D
<</grid>>

Noter que dans ce cas on doit utiliser la notation `short="…"` pour pouvoir utiliser les autres paramètres.

Enfin, il est possible d'aligner un bloc verticalement par rapport aux autres en utilisant le paramètre `align` (équivalent de la propriété CSS [`align-self`](https://developer.mozilla.org/en-US/docs/Web/CSS/align-self)).



## Alignement du texte

Il suffit de placer sur une ligne seule le code `<<center>>` pour centrer du texte :

```
<<center>>
Texte centré
<</center>>
```

On peut procéder de même avec `<<left>>` et `<<right>>` pour aligner à gauche ou à droite.

## Boutons

Il est possible de créer des liens sous la forme de boutons.

Pour cela on utilise l'extension `<<button>>` et ses paramètres :

* `color` : couleur du texte
* `bgcolor` : couleur du fond
* `href` : lien du bouton
* `label` : texte du bouton
* `block=1` : en ajoutant ce paramètre, le bouton prendra toute la largeur de la ligne (bloc)

Il est possible d'utiliser les couleurs avec [leur nom](https://developer.mozilla.org/en-US/docs/Web/CSS/named-color) ou leur code hexadécimal (exemple : `#ff0000` pour rouge).

```
<<button href="https://paheko.cloud/" label="👋 Cliquez ici !" size=20 color="white" bgcolor="darkred">>
```

Donnera le bouton suivant :

<<button href="https://paheko.cloud/" label="👋 Cliquez ici !" size=20 color="white" bgcolor="darkred">>

## Couleurs

Comme sur les [Skyblogs](http://web.archive.org/web/20230821114216/https://decoblog.skyrock.com/), il est possible de mettre en couleur le texte et le fond, et même de créer des dégradés !

Utiliser la syntaxe `<<color COULEUR>>...texte...<</color>>` pour changer la couleur du texte, ou `<<bgcolor COULEUR>>...texte...<</bgcolor>>` pour la couleur du fond.

Il est possible d'indiquer plusieurs couleurs, séparées par des espaces, pour créer des dégradés.

```
<<color red>>Rouge !<</color>>
<<bgcolor yellow>>Fond jaune pétant !<</bgcolor>>
<<color cyan green salmon>>Dégradé de texte !<</color>>
<<bgcolor chocolate khaki orange>>Dégradé du fond<</bgcolor>>

<<bgcolor darkolivegreen darkseagreen >>
<<color darkred>>

## Il est aussi possible de faire des blocs colorés

Avec des paragraphes

> Et citations

<</color>>
<</bgcolor>>
```

> <<color red>>Rouge !<</color>>
> <<bgcolor yellow>>Fond jaune pétant !<</bgcolor>>
> <<color cyan green salmon>>Dégradé de texte !<</color>>
> <<bgcolor chocolate khaki orange>>Dégradé du fond<</bgcolor>>
>
> <<bgcolor greenyellow indianred>>
> <<color darkred darkgreen>>
> ## Il est aussi possible de faire des blocs colorés {.no_toc}
>
> Avec des paragraphes
>
> > Et citations
>
> <</color>>
> <</bgcolor>>

Il est possible d'utiliser les couleurs avec [leur nom](https://developer.mozilla.org/en-US/docs/Web/CSS/named-color) ou leur code hexadécimal (exemple : `#ff0000` pour rouge).

**Attention : cette fonctionnalité est rigolote mais doit être utilisé avec parcimonie, en effet cela risque de rendre le texte illisible, notamment pour les personnes daltoniennes.**
