Title: Développer des modules pour Paheko

{{{.nav
* **[Modules](modules.html)**
* [Documentation Brindille](brindille.html)
* [Fonctions](brindille_functions.html)
* [Sections](brindille_sections.html)
* [Filtres](brindille_modifiers.html)
}}}

<<toc aside>>

# Introduction

Depuis la version 1.3, Paheko dispose d'extensions modifiables, nommées **Modules**.

Les modules permettent de créer et modifier des formulaires, des modèles de documents simples, à imprimer, mais aussi de créer des "mini-applications" directement dans l'administration de l'association, avec le minimum de code, sans avoir à apprendre à programmer PHP.

Les modules utilisent le langage [Brindille](brindille.html), aussi utilisé pour le site web (qui est lui-même un module). Avec Brindille on parle d'un **squelette** pour un fichier texte contenant du code Brindille.

Les modules ne permettent pas d'exécuter du code PHP, ni de modifier la base de données en dehors des données du module, contrairement aux [plugins](https://fossil.kd2.org/paheko/wiki?name=Documentation/Plugin&p). Grâce à Brindille, les administrateurs de l'association peuvent modifier ou créer de nouveaux modules sans risques pour le serveur, car le code Brindille ne permet pas d'exécuter de fonctions dangereuses. Les **plugins** eux sont écrits en PHP et ne peuvent pas être modifiés par une association. Du fait des risques de sécurité, seuls les plugins officiels sont proposés sur Paheko.cloud.

# Exemples

Paheko fournit quelques modules par défaut, qui peuvent être modifiés ou servir d'inspiration pour de nouveaux modules :

* Reçu de don simple
* Reçu de paiement simple
* Reçu fiscal
* Cartes de membres
* Heures d'ouverture
* Modèles d'écritures comptables

Ces exemples sont développés directement avec Brindille et peuvent être modifiés ou lus depuis le menu **Configuration**, onglet **Extensions**.

Un module fourni dans Paheko peut être modifié, et en cas de problème il peut être remis à son état d'origine.

D'autres exemples d'utilisation sont imaginables :

* Auto-remplissage de la déclaration de la liste des dirigeants à la préfecture
* Compte de résultat et bilan conforme au modèle du plan comptable
* Formulaires partagés entre la partie privée, et le site web (voir par exemple le module "heures d'ouverture")
* Gestion de matériel prêté par l'association

# Pré-requis

Une connaissance de la programmation informatique est souhaitable pour commencer à modifier ou créer des modules, mais cela n'est pas requis, il est possible d'apprendre progressivement.

# Résumé technique

* Utilisation de la syntaxe Brindille
* Les modules peuvent utiliser toutes les fonctions et boucles de Brindille
* Les modules peuvent stocker et récupérer des données dans la base SQLite dans une table clé-valeur spécifique à chaque module
* Les données du module sont stockées en JSON, on peut faire des requêtes complètes avec l'extension [JSON de SQLite](https://www.sqlite.org/json1.html)
* Les données peuvent être validées avant enregistrement en utilisant [JSON Schema](https://json-schema.org/understanding-json-schema/)
* Un module peut également accéder aux données des autres modules
* Un module peut aussi accéder à toutes les données de la base de données, sauf certaines données à risque (voir plus bas)
* Un module ne peut pas modifier les données de la base de données
* Paheko crée automatiquement des index sur les requêtes SQL des modules, permettant de rendre les requêtes rapides

# Structure des répertoires

Chaque module a un nom unique (composé uniquement de lettres minuscules, de tirets bas et de chiffres) et dispose d'un sous-répertoire dans le dossier `modules`. Ainsi le module `recu_don` serait dans le répertoire `modules/recu_don`.

Dans ce répertoire le module peut avoir autant de fichiers qu'il veut, mais certains fichiers ont une fonction spéciale :

* `module.ini` : contient les informations sur le module, voir ci-dessous pour les détails
* `config.html` : si ce squelette existe, un bouton "Configurer" apparaîtra dans la liste des modules (Configuration -> Modules) et affichera ce squelette dans un dialogue
* `icon.svg` : icône du module, qui sera utilisée sur la page d'accueil, si le bouton est activé, et dans la liste des modules. Attention l'élément racine du fichier doit porter l'id `img` pour que l'icône fonctionne (`<svg id="img"...>`), notamment pour que les couleurs du thème s'appliquent à l'icône.
* `README.md` : si ce fichier existe, son contenu sera affiché dans les détails du module

## Snippets

Les modules peuvent également avoir des `snippets`, ce sont des squelettes qui seront inclus à des endroits précis de l'interface, permettant de rajouter des fonctionnalités, ils sont situés dans le sous-répertoire `snippets` du module :

* `snippets/transaction_details.html` : sera inclus en dessous de la fiche d'une écriture comptable
* `snippets/transaction_new.html` : sera inclus au début du formulaire de saisie d'écriture
* `snippets/user_details.html` : sera inclus en dessous de la fiche d'un membre
* `snippets/my_details.html` : sera inclus en dessous de la page "Mes informations personnelles"
* `snippets/my_services.html` : sera inclus en dessous de la page "Mes inscriptions et cotisations"
* `snippets/home_button.html` : sera inclus dans la liste des boutons de la page d'accueil (ce fichier ne sera pas appelé si `home_button` est à `true` dans `module.ini`, il le remplace)

### Snippets MarkDown

Il est également possible, depuis Paheko 1.3.2, d'étendre les fonctionnalités Markdown du site web en créant un snippet dans le répertoire `snippets/markdown/`, par exemple `snippets/markdown/map.html`.

Le snippet sera appelé quand on utilise le tag du même nom dans le contenu du site web. Ici par exemple ça serait `<<map>>`.

Le snippet reçoit ces variables :

* `$params` : les paramètres du tag
* `$block` : booléen, `TRUE` si le tag est seul sur une ligne, ou `FALSE` s'il se situe à l'intérieur d'un texte
* `$content` : le contenu du bloc, si celui-ci est sur plusieurs lignes

Exemple :

```
<<map center="Auckland, New Zealand"

Ceci est la capitale de Nouvelle-Zélande !
>>

Voici un marqueur : <<map marker>>
```

Dans le premier appel, `map.html` recevra ces variables :

```
$params = ['center' => 'Auckland, New Zealand']
$content = "Ceci est la capitale de Nouvelle-Zélande !"
$block = TRUE
```

Dans le second appel, le snippet recevra celles-ci :

```
$params = [0 => 'marker']
$content = NULL
$block = FALSE
```

## Fichier module.ini

Ce fichier décrit le module, au format INI (`clé=valeur`), en utilisant les clés suivantes :

* `name` (obligatoire) : nom du module
* `description` : courte description de la fonctionnalité apportée par le module
* `author` : nom de l'auteur
* `author_url` : adresse web HTTP menant au site de l'auteur
* `home_button` : indique si un bouton pour ce module doit être affiché sur la page d'accueil (`true` ou `false`)
* `menu` : indique si ce module doit être listé dans le menu de gauche (`true` ou `false`)
* `restrict_section` : indique la section auquel le membre doit avoir accès pour pouvoir voir le menu de ce module, parmi `web, documents, users, accounting, connect, config`
* `restrict_level` : indique le niveau d'accès que le membre doit avoir dans la section indiquée pour pouvoir voir le menu de ce module, parmi `read, write, admin`.

Attention : les directives `restrict_section` et `restrict_level` ne contrôlent *que* l'affichage du lien vers le module dans le menu et dans les boutons de la page d'accueil, mais pas l'accès aux pages du module.

# Variables spéciales

Toutes les pages d'un module disposent de la variable `$module` qui contient l'entité du module en cours :

* `$module.name` contient le nom unique (`recu_don` par exemple)
* `$module.label` le libellé du module
* `$module.description` la description
* `$module.config` la configuration du module
* `$module.url` l'adresse URL du module (`https://site-association.tld/m/recu_don/` par exemple)

# Stockage de données

Un module peut stocker des données de deux manières : dans sa configuration, ou dans son stockage de documents JSON.

## Configuration

La première manière est de stocker des informations dans la configuration du module. Pour cela on utilise la fonction `save` et la clé `config` :

```
{{:save key="config" accounts_list="512A,512B" check_boxes=true}}
```

On pourra retrouver ces valeurs dans la variable `$module.config` :

```
{{if $module.config.check_boxes}}
  {{$module.config.accounts_list}}
{{/if}}
```

## Stockage de documents JSON

Chaque module peut stocker ses données dans une base de données clé-document qui stockera les données dans des documents au format JSON dans une table SQLite.

Grâce aux [fonctions JSON de SQLite](https://www.sqlite.org/json1.html) on pourra ensuite effectuer des recherches sur ces documents.

Pour enregistrer il suffit d'utiliser la fonction `save` :

```
{{:save key="facture001" type="facture" date="2022-01-01" label="Vente de petits pains au chocolat" total="42"}}
```

Si la clé indiquée (dans le paramètre `key`) n'existe pas, l'enregistrement sera créé, sinon il sera mis à jour avec les valeurs données.

### Validation

On peut utiliser un [schéma JSON](https://json-schema.org/understanding-json-schema/) pour valider que le document qu'on enregistre est valide :

```
{{:save validate_schema="./document.schema.json" type="facture" date="2022-01-01" label="Vente de petits pains au chocolat" total="42"}}
```

Le fichier `document.schema.json` devra être dans le même répertoire que le squelette et devra contenir un schéma valide. Voici un exemple :

```
{
	"$schema": "https://json-schema.org/draft/2020-12/schema",
	"type": "object",
	"properties": {
		"date": {
			"description": "Date d'émission",
			"type": "string",
			"format": "date"
		},
		"type": {
			"description": "Type de document",
			"type": "string",
			"enum": ["devis", "facture"]
		},
		"total": {
			"description": "Montant total",
			"type": "integer",
			"minimum": 0
		},
		"label": {
			"description": "Libellé",
			"type": "string"
		},
		"description": {
			"description": "Description",
			"type": ["string", "null"]
		}
	},
	"required": [ "type", "date", "total", "label"]
}
```

Si le document fourni n'est pas conforme au schéma, il ne sera pas enregistré et une erreur sera affichée.

#### Propriété non requise

Si vous souhaitez utiliser dans votre document une propriété non requise, il ne faut pas la fournir en paramètre de la fonction `save`.

Si elle est fournie mais vide, il faut aussi autoriser le type `null` (en minuscules) au type de votre propriété.

Exemple :  

	[...]
		"description": {
			"description": "Description",
			"type": ["string", "null"]
		}
	[...]

### Stockage JSON dans SQLite (pour information)

Explication du fonctionnement technique derrière la fonction `save`.

En pratique chaque enregistrement sera placé dans une table SQL dont le nom commence par `module_data_`. Ici la table sera donc nommée `module_data_factures` si le nom unique du module est `factures`.

Le schéma de cette table est le suivant :

```
CREATE TABLE module_data_factures (
  id INTEGER PRIMARY KEY NOT NULL,
  key TEXT NULL,
  document TEXT NOT NULL
);

CREATE UNIQUE INDEX module_data_factures_key ON module_data_factures (key);
```

Comme on peut le voir, chaque ligne dans la table peut avoir une clé unique (`key`), et un ID ou juste un ID auto-incrémenté. La clé unique n'est pas obligatoire, mais peut être utile pour différencier certains documents.

Par exemple le code suivant :

```
{{:save key="facture_43" nom="Facture de courses"}}
```

Est l'équivalent de la requête SQL suivante :

```
INSERT OR REPLACE INTO module_data_factures (key, document) VALUES ('facture_43', '{"nom": "Facture de courses"}');
```

### Récupération et liste de documents

Il sera ensuite possible d'utiliser la boucle `load` pour récupérer les données :

```
{{#load id=42}}
	Ce document est de type {{$type}} créé le {{$date}}.
	<h2>{{$label}}</h2>
	À payer : {{$total}} €
	{{else}}
	Le document numéro 42 n'a pas été trouvé.
{{/load}}
```

Cette boucle `load` permet aussi de faire des recherches sur les valeurs du document :

```
<ul>
{{#load where="$$.type = 'facture'" order="date DESC"}}
	<li>{{$label}} ({{$total}} €)</li>
{{/load}}
</ul>
```

La syntaxe `$$.type` indique d'aller extraire la clé `type` du document JSON.

C'est un raccourci pour la syntaxe SQLite `json_extract(document, '$.type')`.

# Export et import de modules

Il est possible d'exporter un module modifié. Cela créera un fichier ZIP contenant à la fois le code modifié et le code non modifié.

De la même manière il est possible d'importer un module à partir d'un fichier ZIP d'export. Si vous créez votre fichier ZIP manuellement, attention à respecter le fait que le code du module doit se situer dans le répertoire `modules/nom_du_module` du fichier ZIP. Tout fichier ou répertoire situé en dehors de cette arborescence provoquera une erreur et l'impossibilité d'importer le module.

# Restrictions

* Il n'est pas possible de télécharger ou envoyer des données depuis un autre serveur
* Il n'est pas possible d'écrire un fichier local

## Envoi d'e-mail

Voir [la documentation de la fonction `{{:mail}}`](brindille_functions.html#mail)

## Tables et colonnes de la base de données

Pour des raisons de sécurité, les modules ne peuvent pas accéder à toutes les données de la base de données.

Les colonnes suivantes de la table `users` (liste des membres) renverront toujours `NULL` :

* `password`
* `pgp_key`
* `otp_secret`

Tenter de lire les données des tables suivantes résultera également en une erreur :

* emails
* emails_queue
* compromised_passwords_cache
* compromised_passwords_cache_ranges
* api_credentials
* plugins_signals
* config
* users_sessions
* logs