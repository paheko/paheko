# Charte de contribution à Paheko

**[Plus d'infos dans la documentation développeur⋅se.](https://fossil.kd2.org/paheko/wiki?name=Documentation%20d%C3%A9veloppeur)**

Paheko est un logiciel libre sous licence AGPLv3.

L'intégralité du code de Paheko est originale, nous n'utilisons pas de bibliothèques externes.

## Informations techniques

* Langage utilisé : PHP
* Versions supportées : 7.4 et supérieur
* Base de donnée utilisée : SQLite 3 (3.25 et supérieur)
* Contact développeureuses : voir la page [Entraide](https://fossil.kd2.org/paheko/wiki?name=Entraide) pour les listes de discussion et le chat IRC
* On utilise SQLite3 comme base de données qui stocke tout : compta, membres, configuration, fichiers, wiki... Ainsi un seul fichier à sauvegarder et à gérer.
* On suit PSR-4 pour le nommage des classes et namespaces etc.
* Convention de code : principalement PSR-1 et PSR-2, enfin pas à la lettre mais globalement. Voir la [Convention de code complète pour plus d'infos](https://fossil.kd2.org/paheko/wiki?name=Guide+de+style+du+code).

## Logiciel libre, mais pas ouvert à toutes les contributions

Contrairement à de nombreux logiciels, nous n'acceptons pas n'importe quelle contribution de n'importe qui.

Il y a un processus à suivre pour qu'un patch soit accepté.

* Petit correctif de bug ou de faille : envoyez un patch en décrivant bien le problème. 
* Ajout de tests unitaires / fonctionnels : idem
* Autre contribution : **seulement après discussion sur la liste [dev@](https://fossil.kd2.org/paheko/wiki?name=Entraide)**

Les contributions importantes ou de nouvelles fonctionnalités demande une discussion en amont sur la liste dev@. Si vous ne suivez pas ce conseil, nous ne pouvons pas garantir que nous aurons le temps ou l'énergie pour lire votre contribution.

Ne soyez pas vexés si le code exact n'est pas utilisé car nous ré-écrivons intégralement la plupart des patchs.

## Comment proposer du code ?

On utilise Fossil qui sert de gestionnaire de versions, wiki, gestionnaire de tickets, distribution de package, etc. Nous avons aussi un miroir Git si vous préférez Git.

Vous pouvez :

* envoyer un fichier .patch par e-mail (de préférence) sur la liste de développement
* envoyer un bundle Fossil par e-mail
* envoyer une PR/MR sur un des miroirs Git officiel de Paheko

Nous n'utilisons pas Git, donc les PR/MR n'apparaissent jamais comme fusionnées, mais cela ne veut pas dire que le patch n'a pas été accepté.

Rappel : si c'est autre chose qu'un correctif de bug de quelques lignes, merci de discuter votre projet sur la liste de discussion en amont.

## Utilisation d'IA générative

* Le développement de Paheko est entièrement effectué par des humain⋅e⋅s. Aucun code généré par IA ne se trouve dans Paheko.
* Nous n’acceptons pas de code généré par IA dans Paheko : aucun module, aucun plugin, aucune contribution générée par IA ne sera intégré.
* Les logiciels d’IA génératives (LLM) sont une erreur. Leur fonctionnement repose sur l’exploitation des travailleurs, des biens culturels, et des ressources, dans le seul intérêt de grandes entreprises, qui s’attellent à détruire le monde encore plus vite, en construisant d’immenses datacenters, qui accaparent les ressources énergétiques et en eau. C’est une catastrophe mondiale qui produit et produira de la misère et de la destruction. Toute personne utilisant une IA est complice de cette folie et doit avoir conscience qu’elle participe à la destruction de l’avenir de l’humanité. De ce fait nous ne pouvons que décourager à l’utilisation de ces technologies mortifères.
* L’utilisation du code de Paheko pour l’entraînement des IA est illégal (contraire à la licence et au droit d’auteur). De ce fait toute machine d’IA générative qui indique connaître le fonctionnement de Paheko est dans une position illégale.

Pour les contributions externes (patch, bug, faille de sécurité…) :

* Toute utilisation d’IA doit être annoncée (obligation de transparence).
* Les messages, rapports de failles, etc. doivent être écrits par des humains.
* Les signalements de failles de sécurité générés avec IA (ou à l’aide d’une IA) doivent signaler clairement (au début du message) l’utilisation de l’IA et quel outil a été utilisé.
* Les contributeurs⋅ices externes peuvent utiliser une IA pour identifier les causes d’un bug, ou générer un patch pour corriger le bug. Mais ce code ne sera jamais intégré dans Paheko, il faudra attendre qu’un⋅e humain⋅e ré-écrive le code pour voir votre contribution intégrée.

Pour les modules et plugins :

* Les modules et plugins communautaires (en Brindille) développés par IA peuvent être référencés dans le wiki de Paheko (en indiquant qu’ils sont fait par IA), mais ils ne seront jamais intégrés à Paheko.

Pour plus de détails, vous pouvez consulter [la section sur les contributions IA de Servo](https://book.servo.org/contributing/getting-started.html#ai-contributions) (en anglais).
