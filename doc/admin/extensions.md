[Raccourcis claviers](keyboard.html) | [Extensions Paheko](extensions.html)

<<toc aside>>

# Extensions Paheko

Paheko propose des extensions qui s'appliquent aux textes rédigés en MarkDown et en SkrivML.

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

## Fichiers joints

Pour créer un bouton permettant de voir ou télécharger un fichier joint à la page web, il suffit d'utiliser la syntaxe suivante :

```
<<file|Nom_fichier.ext|Libellé>>
```

* `Nom_fichier.ext` : remplacer par le nom du fichier  (parmi les fichiers joints à la page)
* `Libellé` : indique le libellé du qui sera affiché sur le bouton, si aucun libellé n'est indiqué alors c'est le nom du fichier qui sera affiché

## Sommaire / table des matières automatique

Il est possible de placer le texte `<<toc>>` au début d'un texte pour générer un sommaire automatiquement à partir des titres et sous-titres :

```
<<toc>>
```

Affichera un sommaire comme celui-ci :

<<toc>>

Il est possible de limiter les niveaux en utilisant le paramètre `level` comme ceci :

```
<<toc level=1>>
```

N'affichera que les titres de niveau 1, comme ceci :

<<toc level=1>>

Enfin il est possible de placer la table des matières sur le côté du texte, en utilisant le paramètre `aside` :

```
<<toc level=1 aside>>
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
<<grid #!! column="span 2">>
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

Enfin, il est possible d'aligner un bloc verticalement par rapport aux autres en utilisant le paramètre `align` (équivalent de la propriété CSS [`align-self`](https://developer.mozilla.org/en-US/docs/Web/CSS/align-self)).

## Alignement du texte

Il suffit de placer sur une ligne seule le code `<<center>>` pour centrer du texte :

```
<<center>>
Texte centré
<</center>>
```

On peut procéder de même avec `<<left>>` et `<<right>>` pour aligner à gauche ou à droite.

## Couleurs

Comme sur les [Skyblogs](https://decoblog.skyrock.com/), il est possible de mettre en couleur le texte et le fond, et même de créer des dégradés !

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