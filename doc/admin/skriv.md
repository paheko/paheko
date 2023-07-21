Title: Référence rapide SkrivML - Paheko

<<toc aside>>

# Syntaxe SkrivML

Paheko propose la syntaxe [SkrivML](https://fossil.kd2.org/paheko/doc/trunk/doc/skrivml.html) pour le formatage du texte des pages du site web.

## Styles de texte

| Style | Syntaxe |
| :- | :- |
| *Italique* | `Entourer le texte de ''deux apostrophes''` |
| **Gras** | `Entourer le texte de **deux astérisques**` |
| Texte <ins>Souligné</ins> | `Entourer le texte de deux __tirets bas__.` |
| ~~Barré~~ | `Deux --tirets hauts-- pour barrer.` |
| Texte <sup>Exposant</sup> | `XXI^^ème^^ siècle` |
| Texte <sub>Indice</sub> | `CO,,2,,` |

**Attention :** ces styles ne fonctionnent que si le code entoure des mots complets, ça ne fonctionne pas au milieu de mots.

```
Un **mot** en gras. Mais on ne peut pas cou**per** un mot avec du gras.
```

> Un **mot** en gras. Mais on ne peut pas cou\*\*per** un mot avec du gras.

## Titres

Doivent être précédé d'un ou plusieurs signe égal. Peuvent aussi être suivi du même nombre de signe égal.

```
= Titre niveau 1
== Titre niveau 2
=== Titre niveau 3 ===
==== Titre de niveau 4 ====
```

## Listes

Listes non ordonnées :

```
* Item 1
* Item 2
** Sous-item 2.1
** Sous-item 2.2
*** Sous-item 2.2.1
```

Listes ordonnées :

```
# Item 1
# Item 2
## Sub-item 2.1
## Sub-item 2.2
### Sub-item 2.2.1
```

## Liens

Lien interne :

```
Voir [[cette page|adresse-unique-autre-page]].
```

Lien externe :

```
[[https://paheko.cloud/]]
```
## Tableaux

```
!! Colonne 1 !! Colonne 2
|| Cellule 1 || Cellule 2
|| Cellule 3 || Cellule 4
```

## Autres

Consulter la documentation complète de [SkrivML](https://fossil.kd2.org/garradin/doc/trunk/doc/skrivml.html).

# Extensions

Toutes les extensions se présentent sous la forme d'un code situé entre deux signes **inférieur à** (`<<`) et deux signes **supérieur à** (`>>`), à ne pas confondre avec les guillements français (`«` et `»`).

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


# Raccourcis clavier

Depuis l'édition du texte :

| Raccourci | Action |
| :- | :- |
| <kbd>Ctrl</kbd> + <kbd>G</kbd> | Mettre en gras |
| <kbd>Ctrl</kbd> + <kbd>I</kbd> | Mettre en italique |
| <kbd>Ctrl</kbd> + <kbd>T</kbd> | Mettre en titre |
| <kbd>Ctrl</kbd> + <kbd>L</kbd> | Transformer en lien |
| <kbd>Ctrl</kbd> + <kbd>Shift</kbd> + <kbd>I</kbd> | Insérer une image |
| <kbd>Ctrl</kbd> + <kbd>Shift</kbd> + <kbd>F</kbd> | Insérer un fichier |
| <kbd>Ctrl</kbd> + <kbd>P</kbd> | Prévisualiser |
| <kbd>Ctrl</kbd> + <kbd>S</kbd> | Enregistrer |
| <kbd>F11</kbd> | Activer ou désactiver l'édition plein écran |
| <kbd>F1</kbd> | Afficher l'aide |
| <kbd>Echap</kbd> | Prévisualiser (rappuyer pour revenir à l'édition) |


Depuis la prévisualisation :

| Raccourci | Action |
| :- | :- |
| <kbd>Ctrl</kbd> + <kbd>P</kbd> | Retour à l'édition |

Depuis l'aide ou l'insertion de fichier :

| Raccourci | Action |
| :- | :- |
| <kbd>Echap</kbd> | Fermer et revenir à l'édition |
