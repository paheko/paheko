[Raccourcis claviers](keyboard.html) | [Extensions Paheko](extensions.html)

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

Par défaut les colonnes sont centrées. On peut aussi choisir l'alignement des colonnes à gauche ou à droite en mettant deux points après le ou les tirets de la ligne suivant l'entête :

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

# Extensions Paheko

Paheko propose des extensions permettant :

* d'insérer des images et fichiers joints ;
* d'insérer une table des matières automatique ;
* d'aligner le texte au centre, à gauche, à droite ;
* de créer des mises en page complexes avec des colonnes et grilles ;
* de changer la couleur du texte ou du fond.

[Cliquer ici pour lire la documentation des extensions Paheko](extensions.html)

# Extensions MarkDown

## Surligner un texte

Il est possible de marquer une phrase ou un mot comme surligné en l'entourant de deux signes égal :

```
Ce texte est ==surligné==.
```

> Ce texte est ==surligné==.

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


## Sommaire / table des matières automatique

En plus de la syntaxe `<<toc>>` documentée dans les extensions communes, Paheko supporte aussi les syntaxes suivantes par compatibilité avec [les autres moteurs de rendu MarkDown](https://alexharv074.github.io/2018/08/28/auto-generating-markdown-tables-of-contents.html) : `{:toc}` `[[_TOC_]]` `[toc]`.

Il est aussi possible d'indiquer qu'un titre ne doit pas être inclus dans le sommaire en utilisant la classe `no_toc` comme ceci :

```
## Sous-titre non-inclus {.no_toc}
```

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