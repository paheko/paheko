Title: Référence rapide MarkDown — Paheko

{{{.nav
* [Raccourcis claviers](keyboard.html)
* [Syntaxe MarkDown complète](markdown.html)
* **[Référence rapide MarkDown](markdown_quickref.html)**
}}}

# Référence rapide MarkDown

|Nom | Syntaxe | Rendu | Notes |
| :- | :- | :- | :- |
| Italique | `*italique*` | *italique* | |
| Gras | `**gras**` | **gras** | |
| Gras et italique | `***gras et italique***` | ***gras et italique*** | |
| Barré | `~~barré~~` | ~~barré~~ | [^P] |
| Surligné | `==surligné==` | ==surligné== | [^P] |
| Lien | `[Libellé du lien](adresse)` | [Libellé du lien](https://paheko.cloud/) | |
| Titre niveau 1 | `# Titre 1` | <h1>Titre 1</h1> | |
| Titre niveau 2 | `## Titre 2` | <h2>Titre 2</h2> | |
| Titre niveau 3 | `### Titre 3` | <h3>Titre 3</h3> | |
| Titre niveau 4 | `#### Titre 4` | <h4>Titre 4</h4> | |
| Titre niveau 5 | `##### Titre 5` | <h5>Titre 5</h5> | |
| Titre niveau 6 | `###### Titre 6` | <h6>Titre 6</h6> | |
| Liste | <pre><code>\* Liste 1<br>\* Liste 2</code></pre> | <ul><li>Liste 1</li><li>Liste 2</li></ul> | |
| Liste imbriquée | <pre><code>\* Liste 1<br>  \* Sous-liste 1</code></pre> | <ul><li>Liste 1<ul><li>Sous-liste 1</li></ul></li></ul> | |
| Liste numérotée | <pre><code>1. Liste 1<br>2. Liste 2</code></pre> | <ol><li>Liste 1</li><li>Liste 2</li></ol> | |
| Code dans du texte | Voir ce <code>\`code\`</code> | Voir ce `code` | |
| Bloc de code | <pre><code>\```<br>Bloc de code<br>\```</code></pre> | <pre><code>Bloc de code</code></pre> | |
| Citation | <pre><code>> Citation<br>> Citation</code></pre> | <blockquote>Citation<br>Citation</blockquote> | |
| Tableau | <pre><code>\| Colonne 1 \| Colonne 2 \|<br>\| - \| - \|<br>\| A \| B \|</code></pre> | <table><thead><tr><th>Colonne 1</th><th>Colonne 2</th></tr></thead><tbody><tr><td>A</td><td>B</td></tr></tbody></table> | |
| Ligne horizontale | `----` | <hr /> | |
| Référence à une note de bas de page | `[^1]` | [^1] | [^P] |
| Définition d'une note de bas de page | <pre><code>\[^1]: Définition</code></pre> | [^1] | [^P] |
| Bloc avec classe CSS | <pre><code>{{{.boutons<br />* [Paheko](https://paheko.cloud/)<br />}}}</code></pre> | <div class="boutons"><ul><li><a href="https://paheko.cloud/">Paheko</a></li></ul></div> | [^P] |
| Sommaire / table des matières | `<<toc>>` | *(ne peut être montré sur cette page)* | [^P] |
| Image jointe | `<<image|nom_image.jpg|center|Légende>>` | *(ne peut être montré sur cette page)* | [^P] |
| Fichier joint | `<<file|nom_fichier.pdf|Libellé>>` | *(ne peut être montré sur cette page)* | [^P] |
| Grille à 2 colonnes | <pre><code>\<<grid !!>><br>Colonne 1<br><br>\<<grid>><br>Colonne 2<br><br>\<</grid>></code></pre> | *(ne peut être montré sur cette page)* | [^P] |
| Texte centré | `<<center>>Centre<</center>>` | <div style="text-align: center;">Centre</div> | [^P] |
| Texte aligné à droite | `<<right>>Droite<</right>>` | <div style="text-align: right;">Droite</div> | [^P] |
| Texte coloré | `<<color red>>Rouge<</color>>` | <<color red>>Rouge<</color>> | [^P] |
| Fond coloré | `<<bgcolor green>>Vert<</color>>` | <<bgcolor green>>Vert<</color>> | [^P] |
| Dégradé de texte | `<<color orange cyan>>Orange à cyan<</color>>` | <<color orange cyan>>Orange à cyan<</color>> | [^P] |
| Dégradé de fond | `<<bgcolor orange cyan>>Orange à cyan<</color>>` | <<bgcolor orange cyan>>Orange à cyan<</color>> | [^P] |
| Clavier | `<kbd>Ctrl</kbd> + <kbd>C</kbd>` | <kbd>Ctrl</kbd> + <kbd>C</kbd> | |
| Exemple console | `<samp>Exemple</samp>` | <samp>Exemple</samp> | |
| Variable maths | `<var>ab</var> + <var>cd</var> = 42` | <var>ab</var> + <var>cd</var> = 42 | |
| Texte supprimé | `<del>supprimé</del>` | <del>supprimé</del> | |
| Texte ajouté | `<ins>ajouté</ins>` | <ins>ajouté</ins> | |
| Exposant | `Texte<sup>exposant</sup>` | Texte<sup>exposant</sup> | |
| Indice | `Texte<sub>indice</sub>` | Texte<sub>indice</sub> | |

[^1]: Exemple de note de bas de page
[^P]: Indique une syntaxe qui ne fait pas partie du standard Markdown, mais est spécifique à Paheko.