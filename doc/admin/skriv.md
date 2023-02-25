[Raccourcis claviers](keyboard.html) | [Extensions Paheko](extensions.html)

<<toc aside>>

# Syntaxe SkrivML

Paheko propose la syntaxe [SkrivML](https://fossil.kd2.org/garradin/doc/trunk/doc/skrivml.html) pour le formatage du texte des pages du site web.

Celle-ci est plus intuitive que la syntaxe MarkDown.

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

Consulter la documentation de [SkrivML](https://fossil.kd2.org/garradin/doc/trunk/doc/skrivml.html).

# Extensions Paheko

Paheko propose des extensions permettant :

* d'insérer des images et fichiers joints ;
* d'insérer une table des matières automatique ;
* d'aligner le texte au centre, à gauche, à droite ;
* de créer des mises en page complexes avec des colonnes et grilles ;
* de changer la couleur du texte ou du fond.

[Cliquer ici pour lire la documentation des extensions Paheko](extensions.html)