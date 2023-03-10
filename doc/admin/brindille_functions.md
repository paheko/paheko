# Référence des fonctions Brindille disponibles

<<toc aside>>

## assign

Permet d'assigner une valeur dans une variable :

```
{{:assign blabla="Coucou"}}

{{$blabla}}
```

Attention : certains noms de variables ne sont pas assignables : `value` et `var` (voir ci-dessous).

Il est possible d'assigner toutes les variables d'une section dans une variable en utilisant la syntaxe `.` et en inversant (`.="nom_de_variable"`). Cela permet de capturer le contenu d'une section pour le réutiliser à un autre endroit.

```
{{#pages uri="Informations" limit=1}}
{{:assign .="infos"}}
{{/pages}}

{{$infos.title}}
```

En utilisant le paramètre spécial `var`, tous les autres paramètres passés sont ajoutés à la variable donnée en valeur :

```
{{:assign var="tableau" label="Coucou" name="Pif le chien"}}
{{$tableau.label}}
{{$tableau.name}}
```

De la même manière on peut écraser une variable avec le paramètre spécial `value`:

```
{{:assign var="tableau" value=$infos}}
```

Il est également possible de créer des tableaux avec la syntaxe `[]` dans le nom de la variable :

```
{{:assign var="liste[comptes][530]" label="Caisse"}}
{{:assign var="liste[comptes][512]" label="Banque"}}

{{#foreach from=$liste.comptes}}
{{$key}} = {{$value.label}}
{{/foreach}}
```

## debug

Cette fonction permet d'afficher le contenu d'une ou plusieurs variables :

```
{{:debug test=$title}}
```

Affichera :

```
array(1) {
  ["test"] => string(6) "coucou"
}
```

Si aucun paramètre n'est spécifié, alors toutes les variables définies sont renvoyées. Utile pour découvrir quelles sont les variables accessibles dans une section par exemple.

## http

Permet de modifier les entêtes HTTP renvoyés par la page. Cette fonction doit être appelée au tout début du squelette, avant tout autre code ou ligne vide.

| Paramètre | Fonction |
| - | - |
| `code` | Modifie le code HTTP renvoyé. [Liste des codes HTTP](https://fr.wikipedia.org/wiki/Liste_des_codes_HTTP) |
| `redirect` | Rediriger vers l'adresse URI indiquée en valeur. Seules les adresses internes sont acceptées, il n'est pas possible de rediriger vers une adresse extérieure. |
| `type` | Modifie le type MIME renvoyé |
| `download` | Force la page à être téléchargée sous le nom indiqué. |

Note : si le type `application/pdf` est indiqué, la page sera convertie en PDF à la volée. Il est possible de forcer le téléchargement du fichier en utilisant le paramètre `download`.

Exemples :

```
{{:http code=404}}
{{:http redirect="/Nos-Activites/"}}
{{:http type="application/svg+xml"}}
{{:http type="application/pdf" download="liste_membres_ca.pdf"}}
```

## include

Permet d'inclure un autre squelette.

| Paramètre | Fonction |
| - | - |
| `file` | obligatoire | Nom du squelette à inclure |
| `keep` | optionnel | Liste de noms de variables à conserver |

```
{{:include file="./navigation.html"}}
=> Affiche le contenu du squelette "navigation.html" dans le même répertoire que le squelette d'origine
```

Par défaut, les variables du squelette parent sont transmis au squelette inclus, mais les variables définies dans le squelette inclus ne sont pas transmises au squelette parent. Exemple :

```
{{* Squelette page.html *}}
{{:assign title="Super titre !"}}
{{:include file="./_head.html"}}
{{$nav}}
```
```
{{* Squelette _head.html *}}
<h1>{{$title}}</h1>
{{:assign nav="Accueil > %s"|args:$title}}
```

Dans ce cas, la dernière ligne du premier squelette (`{{$nav}}`) n'affichera rien, car la variable définie dans le second squelette n'en sortira pas. Pour indiquer qu'une variable doit être incluse dans le squelette parent, il faut utiliser le paramètre `keep`:

```
{{:include file="./_head.html" keep="nav"}}
```

On peut spécifier plusieurs noms de variables, séparés par des virgules, et utiliser la notation à points :

```
{{:include file="./_head.html" keep="nav,article.title,name"}}
{{$nav}}
{{$article.title}}
{{$name}}
```

## mail

Permet d'envoyer un e-mail à une adresse indiquée. Le message est toujours envoyé en format texte et l'expéditeur est l'adresse de l'association.

Attention à l'utilisation de cette fonction il n'existe pas de limite d'envoi.

Paramètres requis :

| Paramètre | Fonction |
| - | - |
| `to` | Adresse email destinataire (seule l'adresse e-mail elle-même est acceptée, pas de nom) |
| `subject` | Sujet du message |
| `body` | Corps du message |

Exemple de formulaire de contact :

```
{{if !$_POST.email|check_email}}
<p class="alert">L'adresse e-mail indiquée est invalide.</p>
{{elseif $_POST.antispam != 42}}
<p class="alert">La réponse permettant de savoir si vous êtes un robot a échoué. Vous êtes donc un robot ?</p>
{{elseif $_POST.message|trim == ''}}
<p class="alert">Le message est vide</p>
{{elseif $_POST.send}}
{{:mail to=$config.org_email subject="Formulaire de contact" body="%s a écrit : %s"|args:$_POST.email:$_POST.message}}
<p class="ok">Votre message nous a bien été transmis !</p>
{{/if}}

<form method="post" action="">
<dl>
  <dt><label>Votre e-mail : <input type="email" required name="email" /></label></dt>
  <dt><label>Votre message : <textarea required name="message" cols="50" rows="5"></textarea></label></dt>
  <dt><label>Merci d'écrire "quarante-deux" en chiffres pour confirmer que vous n'êtes pas un robot : <input type="text" name="antispam" required /></label></dt>
</dl>
<p><input type="submit" name="send" value="Envoyer !" /></p>
</form>
```
