Title: Référence des fonctions Brindille

{{{.nav
* [Modules](modules.html)
* [Documentation Brindille](brindille.html)
* **[Fonctions](brindille_functions.html)**
* [Sections](brindille_sections.html)
* [Filtres](brindille_modifiers.html)
}}}

<<toc aside>>

# Fonctions généralistes

## assign

Permet d'assigner une valeur dans une variable.

| Paramètre | Optionnel / obligatoire ? | Fonction |
| :- | :- | :- |
| `.` | optionnel | Assigner toutes les variables du contexte (section) actuel |
| `var` | optionnel | Nom de la variable à créer ou modifier |
| `value` | optionnel | Valeur de la variable |
| `from` | optionnel | Recopier la valeur depuis la variable ayant le nom fourni dans ce paramètre. |

Tous les autres paramètres sont considérés comme des variables à assigner.

Exemple :

```
{{:assign blabla="Coucou"}}

{{$blabla}}
```

Il est possible d'assigner toutes les variables d'une section dans une variable en utilisant le paramètre point `.` (`.="nom_de_variable"`). Cela permet de capturer le contenu d'une section pour le réutiliser à un autre endroit.

```
{{#pages uri="Informations" limit=1}}
{{:assign .="infos"}}
{{/pages}}

{{$infos.title}}
```

Il est aussi possible de remonter dans les sections parentes en utilisant plusieurs points. Ainsi deux points remonteront à la section parente, trois points à la section parente de la section parente, etc.

```
{{#foreach from=$infos item="info"}}
  {{#foreach from=$info item="sous_info"}}
    {{if $sous_info.titre == 'Coucou'}}
      {{:assign ..="info_importante"}}
    {{/if}}
  {{/foreach}}
{{/foreach}}

{{$info_importante.titre}}
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

Il est également possible de créer des tableaux avec la syntaxe `.` dans le nom de la variable :

```
{{:assign var="liste.comptes.530" label="Caisse"}}
{{:assign var="liste.comptes.512" label="Banque"}}

{{#foreach from=$liste.comptes}}
{{$key}} = {{$value.label}}
{{/foreach}}
```

Il est possible de rajouter des éléments à un tableau simplement en utilisant un point seul :

```
{{:assign var="liste.comptes." label="530 - Caisse"}}
{{:assign var="liste.comptes." label="512 - Banque"}}
```

Enfin, il est possible de faire référence à une variable de manière dynamique en utilisant le paramètre spécial `from` :

```
{{:assign var="tableau" a="Coucou" b="Test !"}}
{{:assign var="titre" from="tableau.%s"|args:"b"}}
{{$titre}} -> Affichera "Test !", soit la valeur de {{$tableau.b}}
```

## break

Interrompt une section.

## continue

Passe à l'itération suivante d'une section. Le code situé entre cette instruction et la fin de la section ne sera pas exécuté.

```
{{#foreach from=$list item="event"}}
  {{if $event.date == '2023-01-01'}}
    {{:continue}}
  {{/if}}
  {{$event.title}}
{{/foreach}}
```

Il est possible de passer à l'itération suivante d'une section parente en utilisant un chiffre en paramètre :

```
{{#foreach from=$list item="event"}}
  {{$event.title}}
  {{#foreach from=$event.people item="person"}}
    {{if $person.name == 'bohwaz'}}
      {{:continue 2}}
    {{/if}}
    - {{$person.name}}
  {{/foreach}}
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


## error

Affiche un message d'erreur et arrête le traitement à cet endroit.

| Paramètre | Optionnel / obligatoire ? | Fonction |
| :- | :- | :- |
| `message` | **obligatoire** | Message d'erreur à afficher |
| `code` | facultatif | Code d'erreur HTTP à utiliser |

Exemple :

```
{{if $_POST.nombre != 42}}
	{{:error message="Le nombre indiqué n'est pas 42"}}
{{/if}}
```

## exit

Arrête l'exécution du code immédiatement.

Utile pour le debug principalement :

```
{{if $condition == 1}}
  {{:debug a=42}}
  {{:exit}}
{{/if}}
```

## form_errors

Affiche les erreurs du formulaire courant (au format HTML).

## http

Permet de modifier les entêtes HTTP renvoyés par la page. Cette fonction doit être appelée au tout début du squelette, avant tout autre code ou ligne vide.

| Paramètre | Optionnel / obligatoire ? | Fonction |
| :- | :- | :- |
| `code` | *optionnel* | Modifie le code HTTP renvoyé. [Liste des codes HTTP](https://fr.wikipedia.org/wiki/Liste_des_codes_HTTP) |
| `type` | *optionnel* | Modifie le type MIME renvoyé |
| `download` | *optionnel* | Force la page à être téléchargée sous le nom indiqué. |
| `inline` | *optionnel* | Force la page à être affichée, et peut ensuite être téléchargée sous le nom indiqué (utile pour la génération de PDF : permet d'afficher le PDF dans le navigateur avant de le télécharger). |

Note : si le type `application/pdf` est indiqué (ou juste `pdf`), la page sera convertie en PDF à la volée. Il est possible de forcer le téléchargement du fichier en utilisant le paramètre `download`.

Exemples :

```
{{:http code=404}}
{{:http type="application/svg+xml"}}
{{:http type="pdf" download="liste_membres_ca.pdf"}}
```

## include

Permet d'inclure un autre squelette.

Paramètres :

| Paramètre | Optionnel / obligatoire ? | Fonction |
| :- | :- | :- |
| `file` | **obligatoire** | Nom du squelette à inclure |
| `keep` | *optionnel* | Liste de noms de variables à conserver |
| `capture` | *optionnel* | Si renseigné, au lieu d'afficher le squelette, son contenu sera enregistré dans la variable de ce nom. |
| … | *optionnel* | Tout autre paramètre sera utilisé comme variable qui n'existea qu'à l'intérieur du squelette inclus. |

```
{{* Affiche le contenu du squelette "navigation.html" dans le même répertoire que le squelette d'origine *}}
{{:include file="./navigation.html"}}
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

Dans ce cas, la dernière ligne du premier squelette (`{{$nav}}`) n'affichera rien, car la variable définie dans le second squelette n'en sortira pas. Pour indiquer qu'une variable doit être transmise au squelette parent, il faut utiliser le paramètre `keep`:

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

On peut aussi capturer le résultat d'un squelette dans une variable :

```
{{:include file="./_test.html" capture="test"}}
{{:assign var="test" value=$test|replace:'TITRE':'Ceci est un titre'}}
{{$test}}
```

Il est possible d'assigner de nouvelles variables au contexte du include en les déclarant comme paramètres tout comme on le ferait avec `{{:assign}}` :

```
{{:include file="./_head.html" title='%s documentation'|args:$doc.label visitor=$user}}
```

## captcha

Permet de générer une question qui doit être répondue correctement par l'utilisateur pour valider une action. Utile pour empêcher les robots spammeurs d'effectuer une action.

L'utilisation simplifiée utilise un de ces deux paramètres :

| Paramètre | Fonction |
| :- | :- |
| `html` | Si `true`, crée un élément de formulaire HTML et le texte demandant à l'utilisateur de répondre à la question |
| `verify` | Si `true`, vérifie que l'utilisateur a correctement répondu à la question |

L'utilisation avancée utilise d'abord ces deux paramètres :

| Paramètre | Fonction |
| :- | :- |
| `assign_hash` | Nom de la variable où assigner le hash (à mettre dans un `<input type="hidden" />`) |
| `assign_number` | Nom de la variable où assigner le nombre de la question (à afficher à l'utilisateur) |

Puis on vérifie :

| Paramètre | Fonction |
| :- | :- |
| `verify_hash` | Valeur qui servira comme hash de vérification (valeur du `<input type="hidden" />`) |
| `verify_number` | Valeur qui représente la réponse de l'utilisateur |
| `assign_error` | Si spécifié, le message d'erreur sera placé dans cette variable, sinon il sera affiché directement. |

Exemple :

```
{{if $_POST.send}}
  {{:captcha verify_hash=$_POST.h verify_number=$_POST.n assign_error="error"}}
  {{if $error}}
    <p class="alert">Mauvaise réponse</p>
  {{else}}
    ...
  {{/if}}
{{/if}}

<form method="post" action="">
{{:captcha assign_hash="hash" assign_number="number"}}
<p>Merci de recopier le nombre suivant en chiffres : <tt>{{$number}}</tt></p>
<p>
  <input type="text" name="n" placeholder="1234" />
  <input type="hidden" name="h" value="{{$hash}}" />
  <input type="submit" name="send" />
</p>
</form>
```

## mail

Permet d'envoyer un e-mail à une ou des adresses indiquées (sous forme de tableau).

Restrictions :

* le message est toujours envoyé en format texte ;
* l'expéditeur est toujours l'adresse de l'association ;
* l'envoi est limité à une seule adresse e-mail externe (adresse qui n'est pas celle d'un membre) dans une page ;
* l'envoi est limité à maximum 10 adresses e-mails internes (adresses de membres) dans une page ;
* un message envoyé à une adresse e-mail externe ne peut pas contenir une adresse web (`https://...`) autre que celle de l'association.

Note : il est également conseillé d'utiliser la fonction `captcha` pour empêcher l'envoi de spam.

| Paramètre | Obligatoire ou optionnel ? | Fonction |
| :- | :- | :- |
| `to` | **obligatoire** | Adresse email destinataire (seule l'adresse e-mail elle-même est acceptée, pas de nom) |
| `subject` | **obligatoire** | Sujet du message |
| `body` | **obligatoire** | Corps du message |
| `block_urls` | *optionnel* | (`true` ou `false`) Permet de bloquer l'envoi si le message contient une adresse `https://…` |
| `attach_file` | *optionnel* | Chemin vers un ou plusieurs documents à joindre au message (situé dans les documents) |
| `attach_from` | *optionnel* | Chemin vers un ou plusieurs squelettes à joindre au message (par exemple pour joindre un document généré) |
| `notification` | *optionnel* | Indique que le message est une notification, et non pas un message personnel ou collectif. |

Pour le destinataire, il est possible de spécifier un tableau :

```
{{:assign var="recipients[]" value="membre1@framasoft.net"}}
{{:assign var="recipients[]" value="membre2@chatons.org"}}
{{:mail to=$recipients subject="Coucou" body="Contenu du message\nNouvelle ligne"}}
```

Exemple de formulaire de contact :

```
{{if !$_POST.email|check_email}}
  <p class="alert">L'adresse e-mail indiquée est invalide.</p>
{{elseif $_POST.message|trim == ''}}
  <p class="alert">Le message est vide</p>
{{elseif $_POST.send}}
  {{:captcha verify=true}}
  {{:mail to=$config.org_email subject="Formulaire de contact" body="%s a écrit :\n\n%s"|args:$_POST.email:$_POST.message block_urls=true}}
  <p class="ok">Votre message nous a bien été transmis !</p>
{{/if}}

<form method="post" action="">
<dl>
  <dt><label>Votre e-mail : <input type="email" required name="email" /></label></dt>
  <dt><label>Votre message : <textarea required name="message" cols="50" rows="5"></textarea></label></dt>
  <dt>{{:captcha html=true}}</dt>
</dl>
<p><input type="submit" name="send" value="Envoyer !" /></p>
</form>
```

## redirect

Redirige vers une nouvelle page immédiatement.

Le code situé après cette fonction ne sera pas exécuté. Il est donc important, dans un bloc `#form` de placer cette instruction à la fin, après l'enregistrement (`:save`).

### Fonctionnement simple

Pour simplement rediriger vers une adresse HTTPS interne ou externe. Utile par exemple pour rediriger une page du site vers une autre adresse.

| Paramètre | Obligatoire ou optionnel ? | Fonction |
| :- | :- | :- |
| `url` | obligatoire | Adresse vers laquelle rediriger |
| `permanent` | optionnel | (booléen) Indiquer `TRUE` à ce paramètre pour indiquer une redirection permanente (code HTTP 301). |

```
{{:redirect url="https://kd2.org/" permanent=true}}
```

### Fonctionnement avancé (fenêtre modale)

Dans l'administration de Paheko, une page peut être ouverte dans une `iframe` (fenêtre modale), appelée **dialogue**. Pour cela on utilise `target="_dialog"` sur le lien ou le formulaire, pour que la page s'ouvre dans cette fenêtre modale.

Si le code exécuté se situe dans une fenêtre modale, les paramètres suivants peuvent être utilisés à la place du paramètre `url` :

| Paramètre | Fonction |
| :- | :- |
| `self` | Redirige à l'intérieur de la fenêtre modale. |
| `parent` | Ferme la fenêtre modale et redirige la fenêtre parente vers l'adresse indiquée. |
| `reload` | Ferme la fenêtre modale, et recharge la page parente. |

Cette fonction permet une dégradation progressive : si la page a été ouverte en dehors d'une fenêtre modale (par exemple si l'utilisateur a ouvert le lien dans un nouvel onglet, via un clic droit), alors ces paramètres ne font aucune différence : la page est redirigée vers l'adresse indiquée dans le paramètre.

Si aucun paramètre n'est fourni, cela revient au même que de faire `{{:redirect reload=null}}`.

Il est important de passer quand même une adresse au paramètre `reload`, car si Javascript n'est pas disponible, il faut que l'utilisateur soit bien redirigé vers la page voulue :

```
{{:redirect reload="./details.html?id=%d"|args:$doc.id}}
```

Dans cet exemple, la page sera redirigée 

Il est possible d'utiliser un point d'exclamation au début de l'URL (`!`) pour indiquer une adresse se situant dans l'administration :

```
{{:redirect parent="!users/"}}
```

## api

Permet d'appeler l'API de Paheko, que ça soit sur l'instance locale, en cours, ou une autre instance externe.

Voir la [documentation de l'API](https://paheko.cloud/api) pour la liste des fonctions disponibles.

| Paramètre | Obligatoire ou optionnel ? | Fonction |
| :- | :- | :- |
| `method` | obligatoire | Méthode de requête : `GET` ou `POST` |
| `path` | obligatoire | Chemin de la méthode de l'API à appeler. |
| `fail` | optionnel | Booléen. Si `true`, alors une erreur sera affichée si la requête échoue. Si `false`, aucune erreur ne sera affichée. Défaut : `true`. |
| `assign` | optionnel | Capturer le résultat dans cette variable. |
| `assign_code` | optionnel | Capturer le code de retour dans cette variable. |

Note : les requêtes de type `PUT` ou `POST` qui nécessitent l'envoi d'un fichier (`import`) ne sont pas fonctionnelles pour le moment.

Par défaut, les requêtes sont réalisées sur la base de données locale, dans ce cas les paramètres suivants sont également disponibles :

| Paramètre | Obligatoire ou optionnel ? | Fonction |
| :- | :- | :- |
| `access` | optionnel | Niveau d'autorisation de l'API (défaut : `admin`). |


```
{{:assign var="users." value=42}}
{{:api
  method="POST"
  path="accounting/transaction"
  assign="result"

  id_year=1
  type="revenue"
  date="01/01/2023"
  label="Don de Ada Lovelace"
  reference="DON-0001"
  payment_reference="Credit Mutuel 00042"
  amount="51,49"
  debit="756"
  credit="512A"
  linked_users=$users
}}

L'écriture n°{{$result.id}} a été créée.
```

Mais cette fonction permet également d'appeler une API Paheko distante, dans ce cas les paramètres suivants sont nécessaires :

| Paramètre | Obligatoire ou optionnel ? | Fonction |
| :- | :- | :- |
| `url` | obligatoire | Adresse HTTP de l'instance Paheko distante. |
| `user` | obligatoire | Identifiant d'accès à l'API distante. |
| `password` | obligatoire | Mot de passe d'accès à l'API distante. |

```
{{:api
  method="POST"
  path="sql"
  sql="SELECT * FROM users;"
  url="https://mon-asso.paheko.cloud/"
  user="zmgyfr1qnm"
  password="OAqFTLFzujJWr6lLn1Mu7w"
  assign="result"
  assign_code="code"
  fail=false
}}

{{if $code == 200}}
  Il y a {{$result.count}} résultats.
{{else}}
  La requête a échoué : code {{$code}} — {{$result.error}}
{{/if}}

```

## csv

Permet de demander à l'utilisateur de charger un fichier CSV, XLSX (Excel) ou ODS (LibreOffice), et ensuite d'associer les colonnes pour permettre d'utiliser ces données dans une boucle.

| Paramètre | Obligatoire ou optionnel ? | Fonction |
| :- | :- | :- |
| action | obligatoire | Action à réaliser : `initialize`, `form`, `cancel_button`, `clear` |
| name | optionnel | Définit le nom du fichier, utile s'il y a plusieurs fichiers CSV dans le même module. |
| assign | optionnel | Assigner le tableau indiquant les informations du fichier CSV à la variable donnée en valeur. |
| file | optionnel | Si ce paramètre est renseigné, le fichier correspondant sera chargé depuis les documents, sinon un formulaire d'upload sera proposé. |

Paramètres pour l'action `initialize` :

| Paramètre | Obligatoire ou optionnel ? | Fonction |
| :- | :- | :- |
| columns | obligatoire | Tableau des colonnes qui pourront être utilisées, sous la forme clé => valeur. |
| mandatory_columns | optionnel | Liste des clés de colonnes qui sont obligatoires. |

Cette fonction est très puissante mais un peu complexe.

Il faut commencer par définir les colonnes que nous voudrons pouvoir utiliser, dans un tableau :

```
{{:assign var="columns"
  date="Date"
  name="Nom"
  address="Adresse"
}}
```

La valeur représente le libellé par défaut de la colonne dans le tableau. La clé représente elle la clé unique qu'on pourra ensuite utiliser dans les données (voir plus bas).

On peut ensuite définir les colonnes que nous souhaitons rendre obligatoire (en utilisant la clé unique) :

```
{{:assign var="mandatory_columns." value="name"}}
{{:assign var="mandatory_columns." value="date"}}
```

Si ces colonnes ne sont pas fournies dans le fichier importé, l'import ne pourra pas continuer.

On commence ensuite la procédure de chargement du CSV :

```
{{:csv action="initialize" name="import_noms" columns=$columns mandatory_columns=$mandatory_columns assign="csv"}}
```

La variable `$csv` contiendra ensuite les informations sur le fichier CSV actuellement chargé. Le tableau de cette variable contiendra les clés suivantes :

* `ready` (booléen) : vaut `true` quand le fichier est chargé et que l'utilisateur a fait correspondre les colonnes
* `loaded` (booléen) : vaut `true` quand le fichier est chargé
* `columns` (tableau) : les colonnes, définies dans l'appel avec l'action `initialize`
* `mandatory_columns` (tableau) : les colonnes requises, définies dans l'appel avec l'action `initialize`
* `file_name` (texte) : le nom du fichier uploadé

Les clés suivantes ne sont renseignées que quand `ready` vaut `true` :

* `translation_table` (tableau) : le tableau associatif entre le numéro de colonne du fichier CSV (clé) et le nom des colonnes définies (valeur)
* `header` (tableau) : la première ligne du fichier CSV chargé
* `rows` (tableau) : les lignes du fichier CSV
* `count` (entier) : le nombre de lignes du fichier CSV chargé
* `skip` (entier) : le nombre de lignes à ignorer (généralement `1`, car la première ligne contient les entêtes des colonnes)

À ce stade, rien n'est affiché. Il faut commencer par afficher le formulaire de chargement et de choix des colonnes si le fichier n'est pas encore chargé :

```
{{if !$csv.ready}}

  {{:csv action="form"}}

{{/if}}
```

Cet appel génère le formulaire complet HTML (`<form>…</form>`), il n'y a rien besoin d'ajouter. Il gère à la fois le formulaire de sélection du fichier CSV, et le formulaire permettant d'associer les colonnes du CSV aux colonnes demandées.

Pour exploiter ensuite les données du CSV il faut d'abord vérifier qu'il est prêt à être utilisé, avec la variable `$csv.ready` :

```
{{if $csv.ready}}

  {{#foreach from=$csv.rows item="row"}}
    Nom : {{$row.name}}<br />
    Adresse : {{$row.address}}<br />
  {{/foreach}}

{{/if}}
```

Il est conseillé d'ajouter dans la page du résultat un bouton pour annuler la procédure. Dans ce cas le CSV chargé sur le serveur sera supprimé :

```
<form method="post" action="">
  <p>{{:csv action="cancel_button"}}</p>
</form>
```

Quand on a terminé avec le CSV, de la même manière il faut faire appel à l'action `clear`.

Note : il est possible de combiner l'usage de la fonction `csv` avec le paramètre `from` de `save` pour enregistrer en une fois toutes les lignes :

```
{{if $csv.ready}}
  {{:save type="entry" from=$csv.rows validate_schema="./entry.schema.json"}}
  {{:csv action="clear"}}
  {{:redirect to="./"}}
{{/if}}
```

### Chargement d'un fichier local

Si le paramètre `file` n'est pas spécifié, un formulaire d'envoi de fichier sera affiché. S'il est renseigné avec le chemin vers un fichier stocké dans Paheko, c'est ce fichier qui sera chargé en mémoire lors de l'exécution de l'action `initialize`. Exemple :

```
{{:csv action="initialize" file="documents/test.ods" columns=$columns assign="csv"}}

{{if !$csv.ready}}
  {{:csv action="form"}}
{{else}}
  {{:debug csv=$csv}}
{{/if}}
```

Comme le fichier est chargé en mémoire lors de la première exécution de l'action `initialize`, si le fichier est modifié après cette première exécution, les modifications ne seront pas visibles. Il faut donc exécuter `{{:csv action="clear"}}` pour pouvoir "recharger" le fichier en mémoire à partir des documents.

## signature

Affiche la signature de l'association (en HTML), ou son logo si aucune signature n'a été choisie.

# Fonctions relatives aux Modules

## save

Enregistre des données, sous la forme d'un document, dans la base de données, pour le module courant.

| Paramètre | Obligatoire ou optionnel ? | Fonction |
| :- | :- | :- |
| `key` | optionnel | Clé unique du document |
| `id` | optionnel | Numéro unique du document |
| `validate_schema` | optionnel | Fichier de schéma JSON à utiliser pour valider les données avant enregistrement |
| `validate_only` | optionnel | Liste des paramètres à valider (par exemple pour ne faire qu'une mise à jour partielle), séparés par des virgules. |
| `assign_new_id` | optionnel | Si renseigné, le nouveau numéro unique du document sera indiqué dans cette variable. |
| `from` | optionnel | Si renseigné avec un tableau, chaque entrée du tableau sera traitée comme un élément à enregistrer. |
| `replace` | optionnel | (Booléen) Si ce paramètre vaut `true`, alors le contenu du document sera écrasé, au lieu d'être fusionné. |
| … | optionnel | Autres paramètres : traités comme des valeurs à enregistrer dans le document |

Si ni `key` ni `id` ne sont indiqués, un nouveau document sera créé avec un nouveau numéro (ID) unique.

### Mise à jour

Si le document indiqué existe déjà, il sera mis à jour. Les valeurs nulles (`NULL`) seront effacées.

```
{{:save key="facture_43" nom="Atelier mobile" montant=250}}
```

Enregistrera dans la base de données le document suivant sous la clé `facture_43` :

```
{"nom": "Atelier mobile", "montant": 250}
```

Exemple de mise à jour :

```
{{:save key="facture_43" montant=300}}
```

Seul le montant sera modifié, le nom ne sera pas modifié.

Par contre en utilisant le paramètre `replace`, le document sera écrasé :

```
{{:save key="facture_43" replace=true nom="Vente de vélo"}}
```

Donnera :

```
{"nom": "Vente de vélo"}
```

Le montant est donc supprimé.

### Récupérer l'identifiant du document ajouté

Exemple de récupération du nouvel ID :

```
{{:save titre="Coucou !" assign_new_id="id"}}
Le document n°{{$id}} a bien été enregistré.
```

### Enregistrer plusieurs documents en une fois

Le paramètre `from` est équivalent à appeler la fonction `save` dans une boucle. Ainsi au lieu de :

```
{{:assign var="documents." title="Titre 1"}}
{{:assign var="documents." title="Titre 2"}}
{{#foreach from=$documents item="doc"}}
  {{:save title=$doc.title validate_schema="./document.schema.json"}}
{{/foreach}}
```

On peut simplement utiliser :

```
{{:assign var="documents." title="Titre 1"}}
{{:assign var="documents." title="Titre 2"}}
{{:save from=$documents validate_schema="./document.schema.json"}}
```

### Validation avec un schéma JSON

```
{{:save titre="Coucou" texte="Très long" validate_schema="./document.schema.json"}}
```

Pour ne valider qu'une partie du schéma, par exemple si on veut faire une mise à jour du document :

```
{{:save key="test" titre="Coucou" validate_schema="./document.schema.json" validate_only="titre"}}
```

## delete

Supprime un document lié au module courant.

| Paramètre | Obligatoire ou optionnel ? | Fonction |
| :- | :- | :- |
| `key` | optionnel | Clé unique du document |
| `id` | optionnel | Numéro unique du document |

Il est possible de spécifier d'autres paramètres, ou une clause `where` et des paramètres dont le nom commence par deux points.

* Supprimer le document avec la clé `facture_43` : `{{:delete key="facture_43"}}`
* Supprimer le document avec la clé `ABCD` et dont la propriété `type` du document correspond à la valeur `facture` : `{{:delete key="ABCD" type="facture"}}`
* Supprimer tous les documents : `{{:delete}}`
* Supprimer tous les documents ayant le type `facture` : `{{:delete type="facture"}}`
* Supprimer tous les documents de type `devis` ayant une date dans le passé : `{{:delete :type="devis" where="$$.type = :type AND $$.date < datetime('now', 'localtime')"}}`

## read

Lire un fichier stocké dans les fichiers du code du module.

| Paramètre | Obligatoire ou optionnel ? | Fonction |
| :- | :- | :- |
| `file` | obligatoire | Chemin du fichier à lire |
| `assign` | optionnel | Variable dans laquelle placer le contenu du fichier. |

Si le paramètre `assign` n'est pas utilisé, le contenu du fichier sera affiché directement.

Exemple pour lire un fichier JSON :

```
{{:read file="baremes.json" assign="baremes"}}
{{:assign baremes=$baremes|json_decode}}
Barème kilométrique pour une voiture de 3 CV : {{$baremes.voiture.3cv}}
```

Exemple pour lire un fichier CSV :

```
{{:read file="baremes.csv" assign="baremes"}}
{{:assign baremes=$baremes|trim|explode:"\n"}}

{{#foreach from=$baremes item="line"}}
  {{:assign bareme=$line|str_getcsv}}
  Nom du barème : {{$bareme.0}}
  Calcul : {{$bareme.1}}
{{/foreach}}
```

## admin_header

Affiche l'entête de l'administration de l'association.

| Paramètre | Obligatoire ou optionnel ? | Fonction |
| :- | :- | :- |
| `title` | *optionnel* | Titre de la page |
| `layout` | *optionnel* | Aspect de la page. Peut être `public` pour une page publique simple (sans le menu), ou `raw` pour une page vierge (sans aucun menu ni autre élément). Défaut : vide (affichage du menu) |
| `current` | *optionnel* | Indique quel élément dans le menu de gauche doit être marqué comme sélectionné |
| `custom_css` | *optionnel* | Fichier CSS supplémentaire à appeler dans le `<head>` |

```
{{:admin_header title="Gestion des dons" current="acc"}}
```

Liste des choix possibles pour `current` :

* `home` : menu Accueil
* `users` : menu Membres
* `users/new` : sous-menu "Ajouter" de Membres
* `users/services` : sous-menu "Activités et cotisations" de Membres
* `users/mailing` : sous-menu "Message collectif" de Membres
* `acc` : menu Comptabilité
* `acc/new` : sous-menu "Saisie" de Comptabilité
* `acc/accounts` : sous-menu "Comptes"
* `acc/simple` : sous-menu "Suivi des écritures"
* `acc/years` : sous-menu "Exercices et rapports"
* `docs` : menu Documents
* `web` : menu Site web
* `config` : menu Configuration
* `me` : menu "Mes infos personnelles"
* `me/services` : sous-menu "Mes activités et cotisations"

Exemple d'utilisation de `custom_css` depuis un module :

```
{{:admin_header title="Mon module" custom_css="./style.css"}}
```

## admin_footer

Affiche le pied de page de l'administration de l'association.

```
{{:admin_footer}}
```

## delete_form

Affiche un formulaire demandant la confirmation de suppression d'un élément.

| Paramètre | Obligatoire ou optionnel ? | Fonction |
| :- | :- | :- |
| `legend` | **obligatoire** | Libellé de l'élément `<legend>` du formulaire |
| `warning` | **obligatoire** | Libellé de la question de suppression (en gros en rouge) |
| `alert` | *optionnel* | Message d'alerte supplémentaire (bloc jaune) |
| `info` | *optionnel* | Informations liées à la suppression (expliquant ce qui va être impacté par la suppression) |
| `confirm` | *optionnel* | Libellé de la case à cocher pour la suppression, si ce paramètre est absent ou `NULL`, la case à cocher ne sera pas affichée. |

Le formulaire envoie un `POST` avec le bouton ayant le nom `delete`. Si le paramètre `confirm` est renseigné, alors la case à cochée aura le nom `confirm_delete`.

Exemple :

```
{{#load id=$_GET.id assign="invoice"}}
{{else}}
  {{:error message="Facture introuvable"}}
{{/load}}

{{#form on="delete"}}
  {{if !$_POST.confirm_delete}}
    {{:error message="Merci de cocher la case"}}
  {{/if}}
  {{:delete id=$invoice.id}}
{{/form}}

{{:form_errors}}

{{:delete_form
  legend="Suppression d'une facture"
  warning="Supprimer la facture n°%d ?"|args:$invoice.id
  info="Le devis lié sera également supprimé"
  alert="La facture sera définitivement perdue !"
  confirm="Cocher cette case pour confirmer la suppression de la facture"
}}
```

## dropdown

Crée un champ qui ressemble à un `<select>` en HTML, mais permet une formattage plus avancé, et est utilisé pour de la navigation.

Ce n'est pas un champ de formulaire, aucune valeur n'est retournée s'il est utilisé dans un formulaire.

| Paramètre | Obligatoire ou optionnel ? | Fonction |
| :- | :- | :- |
| `options` | **obligatoire** | Tableau des options |
| `title` | **obligatoire** | Libellé |
| `value` | facultatif | Valeur de l'option actuellement sélectionnée |

Chaque option peut contenir les clés suivantes :

| Paramètre | Fonction |
| :- | :- |
| `value` | Valeur de l'élément |
| `html` | Contenu HTML de l'élément |
| `label` | Libellé de l'élément (utilisé si `html` n'est pas renseigné) |
| `aside` | Élément à afficher en petit, à droite du libellé. |
| `href` | Si renseigné, le contenu sera dans un lien pointant vers cette adresse. |

Exemple :

```
{{:assign var="options." value="42" label="Membres cachés" aside="525 membres" href="?cat=42"}}
{{:assign var="options." value="43" label="Tous les membres" aside="1234 membres" href="?cat=43"}}
{{:dropdown value=42 options=$options title="Choisir une catégorie de membres"}}
```

## input

Crée un champ de formulaire HTML. Cette fonction est une extension à la balise `<input>` en HTML, mais permet plus de choses.

| Paramètre | Obligatoire ou optionnel ? | Fonction |
| :- | :- | :- |
| `name` | **obligatoire** | Nom du champ |
| `type` | **obligatoire** | Type de champ |
| `required` | *optionnel* | Mettre à `true` si le champ est obligatoire |
| `label` | *optionnel* | Libellé du champ |
| `help` | *optionnel* | Texte d'aide, affiché sous le champ |
| `default` | *optionnel* | Valeur du champ par défaut, si le formulaire n'a pas été envoyé, et que la valeur dans `source` est vide |
| `source` | *optionnel* | Source de pré-remplissage du champ. Si le nom du champ est `montant`, alors la valeur de `[source].montant` sera affichée si présente. |

Si `label` ou `help` sont spécifiés, le champ sera intégré à une balise HTML `<dd>`, et le libellé sera intégré à une balise `<dt>`. Dans ce cas il faut donc que le champ soit dans une liste `<dl>`. Si ces deux paramètres ne sont pas spécifiés, le champ sera le seul tag HTML.

```
<dl>
	{{:input name="amount" type="money" label="Montant" required=true}}
</dl>
```

Note : le champ aura comme `id` la valeur `f_[name]`. Ainsi un champ avec `amount` comme `name` aura `id="f_amount"`.

### Valeur du champ

La valeur du champ est remplie avec :

* la valeur dans `$_POST` qui correspond au `name` ;
* sinon la valeur dans `source` (tableau) avec le même nom (exemple : `$source[name]`) ;
* sinon la valeur de `default` est utilisée.

Note : le paramètre `value` n'est pas supporté sauf pour checkbox et radio.

### Types de champs supportés

* les types classiques de `input` en HTML : text, search, email, url, file, date, checkbox, radio, password, etc.
  * Note : pour checkbox et radio, il faut utiliser le paramètre `value` en plus pour spécifier la valeur.
* `textarea`
* `money` créera un champ qui attend une valeur de monnaie au format décimal
* `datetime` créera un champ date et un champ texte pour entrer l'heure au format `HH:MM`
* `radio-btn` créera un champ de type radio mais sous la forme d'un gros bouton
* `select` crée un sélecteur de type `<select>`. Dans ce cas il convient d'indiquer un tableau associatif dans le paramètre `options`.
* `select_groups` crée un sélecteur de type `<select>`, mais avec des `<optgroup>`. Dans ce cas il convient d'indiquer un tableau associatif à deux niveaux dans le paramètre `options`.
* `list` crée un champ permettant de sélectionner un ou des éléments (selon si le paramètre `multiple` est `true` ou `false`) dans un formulaire externe. Le paramètre `can_delete` indique si l'utilisateur peut supprimer l'élément déjà sélectionné (si `multiple=false`). La sélection se fait à partir d'un  formulaire  dont l'URL doit être spécifiée dans le paramètre `target`. Les formulaires actuellement supportés sont :
  * `!acc/charts/accounts/selector.php?types=X` pour sélectionner un compte du plan comptable, où X est une liste de types de comptes qu'il faut permettre de choisir (séparés par des `|`)
  * `!acc/charts/accounts/selector.php?codes=X` pour sélectionner un compte du plan comptable, où X est une liste de codes de comptes qu'il faut permettre de choisir (séparés par des `|`). Il est possible d'utiliser une astérisque pour inclure les sous-comptes : `codes=512*|580*`
  * `!users/selector.php` pour sélectionner un membre

Note : pour les champs de type `select` et `select_groups` il est possible de spécifier le paramètre `default_empty` pour la valeur vide par défaut du champ. `default_empty="Tous"` affichera ainsi la valeur `Tous` en première option du select. Si cette option est sélectionnée une chaîne vide sera envoyée.

## button

Affiche un bouton, similaire à `<button>` en HTML, mais permet d'ajouter une icône par exemple.

```
{{:button type="submit" name="save" label="Créer ce membre" shape="plus" class="main"}}
```

| Paramètre | Obligatoire ou optionnel ? | Fonction |
| :- | :- | :- |
| `type` | optionnel | Type du bouton |
| `name` | optionnel | Nom du bouton |
| `label` | optionnel | Label du bouton |
| `shape` | optionnel | Affiche une icône en préfixe du label |
| `class` | optionnel | Classe CSS |
| `title` | optionnel | Attribut HTML `title` |
| `disabled` | optionnel | Désactive le bouton si `true` |


## link

Affiche un lien.

```
{{:link href="!users/new.php" label="Créer un nouveau membre"}}
```

| Paramètre | Obligatoire ou optionnel ? | Fonction |
| :- | :- | :- |
| `href` | **obligatoire** | Adresse du lien |
| `label` | **obligatoire** | Libellé du lien |
| `target` | *optionnel* | Cible du lien, utiliser `_dialog` pour que le lien s'ouvre dans une fenêtre modale. |


Préfixer l'adresse par "!" donnera une URL absolue en préfixant l'adresse par l'URL de l'administration.
Sans "!", l'adresse générée sera relative au contexte d'appel (module/plugin ou squelette site web).


## linkbutton

Affiche un lien sous forme de faux bouton, avec une icône si le paramètre `shape` est spécifié.

```
{{:linkbutton href="!users/new.php" label="Créer un nouveau membre" shape="plus"}}
```

| Paramètre | Obligatoire ou optionnel ? | Fonction |
| :- | :- | :- |
| `href` | **obligatoire* | Adresse du lien |
| `label` | **obligatoire** | Libellé du bouton |
| `target` | *optionnel* | Cible de l'ouverture du lien |
| `shape` | *optionnel* | Affiche une icône en préfixe du label |

Si on utilise `target="_dialog"` alors le lien s'ouvrira dans une fenêtre modale (iframe) par dessus la page actuelle.

Si on utilise `target="_blank"` alors le lien s'ouvrira dans un nouvel onglet.

## icon

Affiche une icône.

```
{{:icon shape="print"}}
```

| Paramètre | Obligatoire ou optionnel ? | Fonction |
| :- | :- | :- |
| `shape` | **obligatoire** | Forme de l'icône. |


### Formes d'icônes disponibles

![](shapes.png)

## user_field

Affiche un champ de la fiche membre.

| Paramètre | Obligatoire ou optionnel ? | Fonction |
| :- | :- | :- |
| `name` | **obligatoire** | Nom du champ. |
| `value` | **obligatoire** | Valeur du champ. |

## edit_user_field

Afficher un champ de formulaire pour modifier un champ de la fiche membre.

| Paramètre | Obligatoire ou optionnel ? | Fonction |
| :- | :- | :- |
| `name` | **obligatoire** | Nom du champ. |
| `source` | *optionnel* | Source de pré-remplissage du champ. Si le nom du champ est `montant`, alors la valeur de `[source].montant` sera utilisée comme valeur du champ. |

# Gestion de fichiers dans les modules

Les modules peuvent stocker des fichiers, mais seulement dans leur propre contexte. Un module ne peut pas gérer les fichiers du site web, des écritures comptables, des membres, ou des autres modules, il ne peut gérer que ses propres fichiers.

Quand les données d'un module sont supprimé, les fichiers du module sont aussi supprimés.

Mais si le module stocke des fichiers liés à un document JSON (par exemple dans un sous-répertoire pour chaque module), c'est au code du module de s'assurer que les fichiers seront supprimés lors de la suppression du document.

Par défaut, tous les fichiers des modules sont en accès restreint : ils ne peuvent être vus et modifiés que par les membres connectés qui sont au niveau d'accès indiqué dans les paramètres `restrict_section` et `restrict_level` du fichier `module.ini`.

Pour qu'un fichier soit visible publiquement aux personnes non connectées, il faut le placer dans le sous-répertoire `public` du module.

Attention : de par ce fonctionnement, **tous les fichiers** d'un module sont potentiellement accessibles par **tous les membres ayant accès au module** et connaissant le nom du fichier, indépendamment des fonctions `admin_files` et `delete_file` !

Il est donc recommandé de ne pas utiliser ce mécanisme pour stocker des données personnelles ou des données sensibles.

## admin_files

Affiche (dans le contexte de l'administration) la liste des fichiers dans un sous-répertoire, et éventuellement la possibilité d'en ajouter ou de les supprimer.

| Paramètre | Obligatoire ou optionnel ? | Fonction |
| :- | :- | :- |
| `path` | optionnel | Chemin du sous-répertoire où sont stockés les fichiers |
| `upload` | optionnel | Booléen. Si `true`, le bouton pour ajouter des fichiers sera affiché. (Défaut : `false`) |
| `edit` | optionnel | Booléen. Si `true`, le bouton pour modifier ou supprimer les fichiers existants sera affiché. (Défaut : `false`) |
| `use_trash` | optionnel | Booléen. Si `false`, le fichier sera supprimé, sans passer par la corbeille. Défaut : `true` |

Exemple pour afficher la liste des fichiers du sous-répertoire `facture43` et permettre de rajouter de nouveaux fichiers :

```
{{:admin_files path="facture43" upload=true edit=false}}
```

## delete_file

Supprimer un fichier ou un répertoire lié au module courant.

| Paramètre | Obligatoire ou optionnel ? | Fonction |
| :- | :- | :- |
| `path` | obligatoire | Chemin du fichier ou répertoire |

Exemple pour supprimer un fichier seul :

```
{{:delete_file path="facture43/justificatif.pdf"}}
```

Pour supprimer un répertoire et tous les fichiers dedans :

```
{{:delete_file path="facture43"}}
```
