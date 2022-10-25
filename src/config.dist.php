<?php

/**
 * Ce fichier représente un exemple des constantes de configuration
 * disponibles pour Garradin.
 *
 * NE PAS MODIFIER CE FICHIER!
 *
 * Pour configurer Garradin, copiez ce fichier en 'config.local.php'
 * puis décommentez et modifiez ce dont vous avez besoin.
 */

// Nécessaire pour situer les constantes dans le bon namespace
namespace Garradin;

/**
 * Clé secrète, doit être unique à chaque instance de Garradin
 *
 * Ceci est utilisé afin de sécuriser l'envoi de formulaires
 * (protection anti-CSRF).
 *
 * Cette valeur peut être modifiée sans autre impact que la déconnexion des utilisateurs
 * actuellement connectés.
 *
 * Si cette constante n'est définie, Garradin ajoutera automatiquement
 * une valeur aléatoire dans le fichier config.local.php.
 */

//const SECRET_KEY = '3xUhIgGwuovRKOjVsVPQ5yUMfXUSIOX2GKzcebsz5OINrYC50r';

/**
 * @var null|int|array
 *
 * Forcer la connexion locale
 *
 * Si un numéro est spécifié, alors le membre avec l'ID correspondant à ce
 * numéro sera connecté (sans besoin de mot de passe).
 *
 * Exemple: LOCAL_LOGIN = 42 connectera automatiquement le membre avec id = 42
 * Attention à ne pas utiliser en production !
 *
 * Si le nombre spécifié est -1, alors c'est le premier membre trouvé qui
 * peut gérer la configuration (et donc modifier les droits des membres)
 * qui sera connecté.
 *
 * Si un tableau est spécifié, alors Garradin considérera que l'utilisateur
 * connecté fourni dans le tableau n'est pas un membre.
 * Voir la documentation sur l'utilisation avec SSO et LDAP pour plus de détails.
 *
 * Exemple :
 * const LOCAL_LOGIN = [
 * 	'user' => ['_name' => 'bohwaz'],
 * 	'permissions' => ['users' => 9, 'config' => 9]
 * ];
 *
 * Défault : null (connexion automatique désactivée)
 */

//const LOCAL_LOGIN = null;

/**
 * Autoriser (ou non) l'import de sauvegarde qui a été modifiée ?
 *
 * Si mis à true, un avertissement et une confirmation seront demandés
 * Si mis à false, tout fichier SQLite importé qui ne comporte pas une signature
 * valide (hash SHA1) sera refusé.
 *
 * Ceci ne s'applique qu'à la page "Sauvegarde et restauration" de l'admin,
 * il est toujours possible de restaurer une base de données non signée en
 * la recopiant à la place du fichier association.sqlite
 *
 * Défaut : true
 */

//const ALLOW_MODIFIED_IMPORT = true;

/**
 * Répertoire où se situe le code source de Garradin
 *
 * Défaut : répertoire racine de Garradin (__DIR__)
 */

//const ROOT = __DIR__;

/**
 * Répertoire où sont situées les données de Garradin
 * (incluant la base de données SQLite, les sauvegardes, le cache, les fichiers locaux et les plugins)
 *
 * Défaut : sous-répertoire "data" de la racine
 */

//const DATA_ROOT = ROOT . '/data';

/**
 * Répertoire où est situé le cache,
 * exemples : graphiques de statistiques, templates Brindille, etc.
 *
 * Défaut : sous-répertoire 'cache' de DATA_ROOT
 */

//const CACHE_ROOT = DATA_ROOT . '/cache';

/**
 * Répertoire où est situé le cache partagé entre instances
 * Garradin utilisera ce répertoire pour stocker le cache susceptible d'être partagé entre instances, comme
 * le code PHP généré à partir des templates Smartyer.
 *
 * Défaut : sous-répertoire 'shared' de CACHE_ROOT
 */

//const SHARED_CACHE_ROOT = CACHE_ROOT . '/shared';

/**
 * Emplacement du fichier de base de données de Garradin
 *
 * Défaut : DATA_ROOT . '/association.sqlite'
 */

//const DB_FILE = DATA_ROOT . '/association.sqlite';

/**
 * Emplacement de stockage des plugins
 *
 * Défaut : DATA_ROOT . '/plugins'
 */

//const PLUGINS_ROOT = DATA_ROOT . '/plugins';

/**
 * Signaux système
 *
 * Permet de déclencher des signaux sans passer par un plugin.
 * Le fonctionnement des signaux système est strictment identique aux signaux des plugins.
 * Les signaux système sont exécutés en premier, avant les signaux des plugins.
 *
 * Format : pour chaque signal, un tableau comprenant une seule clé et une seule valeur.
 * La clé est le nom du signal, et la valeur est la fonction.
 *
 * Défaut: [] (tableau vide)
 */
//const SYSTEM_SIGNALS = [['files.delete' => 'MyNamespace\Signals::deleteFile'], ['entity.Accounting\Transaction.save.before' => 'MyNamespace\Signals::saveTransaction']];

/**
 * Adresse URI de la racine du site Garradin
 * (doit se terminer par un slash)
 *
 * Défaut : découverte automatique à partir de SCRIPT_NAME
 */

//const WWW_URI = '/asso/';

/**
 * Adresse URL HTTP(S) de Garradin
 *
 * Défaut : découverte à partir de HTTP_HOST ou SERVER_NAME + WWW_URI
 */

//const WWW_URL = 'http://garradin.chezmoi.tld' . WWW_URI;

/**
 * Adresse URL HTTP(S) de l'admin Garradin
 *
 * Défaut : WWW_URL + 'admin/'
 */

//const ADMIN_URL = 'https://admin.garradin.chezmoi.tld/';

/**
 * Affichage des erreurs
 * Si "true" alors un message expliquant l'erreur et comment rapporter le bug s'affiche
 * en cas d'erreur. Sinon rien ne sera affiché.
 *
 * Défaut : false
 *
 * Il est fortement conseillé de mettre cette valeur à false en production !
 */

//const SHOW_ERRORS = false;

/**
 * Envoi des erreurs par e-mail
 *
 * Si renseigné, un email sera envoyé à l'adresse indiquée à chaque fois qu'une erreur
 * d'exécution sera rencontrée.
 * Si "false" alors aucun email ne sera envoyé.
 * Note : les erreurs sont déjà toutes loguées dans error.log à la racine de DATA_ROOT
 *
 * Défaut : false
 */

//const MAIL_ERRORS = false;

/**
 * Envoi des erreurs à une API compatible AirBrake/Errbit/Garradin
 *
 * Si renseigné avec une URL HTTP(S) valide, chaque erreur système sera envoyée
 * automatiquement à cette URL.
 *
 * Si laissé à null, aucun rapport ne sera envoyé.
 *
 * Garradin accepte aussi les rapports d'erreur venant d'autres instances.
 *
 * Pour cela utiliser l'URL https://login:password@garradin.site.tld/api/errors/report
 * (voir aussi API_USER et API_PASSWORD)
 *
 * Les erreurs seront ensuite visibles dans
 * Configuration -> Fonctions avancées -> Journal d'erreurs
 *
 * Défaut : null
 */

//const ERRORS_REPORT_URL = null;

/**
 * Template HTML d'erreur personnalisé (en production)
 *
 * Si SHOW_ERRORS est à FALSE un message d'erreur générique (sans détail technique)
 * est affiché. Il est possible de personnaliser ce message avec cette constante.
 *
 * Voir include/init.php pour le template par défaut.
 */

// const ERRORS_TEMPLATE = null;

/**
 * Activation des détails techniques (utile en auto-hébergement) :
 * - version de PHP
 * - page permettant de visualiser les erreurs présentes dans le error.log
 * - permettre de migrer d'un stockage de fichiers à l'autre
 * - vérification de nouvelle version (sur la page configuration)
 *
 * Ces infos ne sont visibles que par les membres ayant accès à la configuration.
 *
 * Défaut : true
 * (Afin d'aider au rapport de bugs des instances auto-hébergées)
 */

//const ENABLE_TECH_DETAILS = true;

/**
 * Activation du log SQL (option de développement)
 *
 * Si cette constante est renseignée par un chemin de fichier SQLite valide,
 * alors *TOUTES* les requêtes SQL et leur contenu sera logué dans la base de données indiquée.
 *
 * Cette option permet ensuite de parcourir les requêtes via l'interface dans
 * Configuration -> Fonctions avancées -> Journal SQL pour permettre d'identifier
 * les requêtes qui mettent trop de temps, et comment elles pourraient
 * être améliorées. Visualiser les requêtes SQL nécessite d'avoir également activé
 * ENABLE_TECH_DETAILS.
 *
 * ATTENTION : cela signifie que des informations personnelles (mot de passe etc.)
 * peuvent se retrouver dans le log. Ne pas utiliser à moins de tester en développement.
 * Cette option peut significativement ralentir le chargement des pages.
 *
 * Défaut : null (= désactivé)
 * @var string|null
 */
// const SQL_DEBUG = __DIR__ . '/debug_sql.sqlite';

/**
 * Activer la possibilité de faire une mise à jour semi-automatisée
 * depuis fossil.kd2.org.
 *
 * Si mis à TRUE, alors un bouton sera accessible depuis le menu "Configuration"
 * pour faire une mise à jour en deux clics.
 *
 * Il est conseillé de désactiver cette fonctionnalité si vous ne voulez pas
 * permettre à un utilisateur de casser l'installation !
 *
 * Si cette constante est désactivée, mais que ENABLE_TECH_DETAILS est activé,
 * la vérification de nouvelle version se fera quand même, mais plutôt que de proposer
 * la mise à jour, Garradin proposera de se rendre sur le site officiel pour
 * télécharger la mise à jour.
 *
 * Défaut : true
 *
 * @var bool
 */

//const ENABLE_UPGRADES = true;

/**
 * Utilisation de cron pour les tâches automatiques
 *
 * Si "true" on s'attend à ce qu'une tâche automatisée appelle
 * les scripts suivants:
 * - scripts/cron.php toutes les 24 heures (envoi des rappels de cotisation,
 * création des sauvegardes)
 * - scripts/emails.php toutes les 5 minutes environ (envoi des emails en attente)
 *
 * Si "false", les actions de scripts/cron.php seront effectuées quand une personne
 * se connecte. Et les emails seront envoyés instantanément (ce qui peut ralentir ou
 * planter si un message a beaucoup de destinataires).
 *
 * Défaut : false
 */

//const USE_CRON = false;

/**
 * Activation de l'envoi de fichier directement par le serveur web.
 * (X-SendFile)
 *
 * Permet d'améliorer la rapidité d'envoi des fichiers.
 * Supporte les serveurs web suivants :
 * - Apache avec mod_xsendfile (paquet libapache2-mod-xsendfile)
 * - Lighttpd
 *
 * N'activer que si vous êtes sûr que le module est installé et activé (sinon
 * les fichiers ne pourront être vus ou téléchargés).
 * Nginx n'est PAS supporté, car X-Accel-Redirect ne peut gérer que des fichiers
 * qui sont *dans* le document root du vhost, ce qui n'est pas le cas ici.
 *
 * Pour activer X-SendFile mettre dans la config du virtualhost de Garradin:
 * XSendFile On
 * XSendFilePath /var/www/garradin
 *
 * (remplacer le chemin par le répertoire racine de Garradin)
 *
 * Détails : https://tn123.org/mod_xsendfile/
 *
 * Défaut : false
 */

//const ENABLE_XSENDFILE = false;

/**
 * Serveur NTP utilisé pour les connexions avec TOTP
 * (utilisé seulement si le code OTP fourni est faux)
 *
 * Désactiver (false) si vous êtes sûr que votre serveur est toujours à l'heure.
 *
 * Défaut : fr.pool.ntp.org
 */

//const NTP_SERVER = 'fr.pool.ntp.org';

/**
 * Hôte du serveur SMTP, mettre à false (défaut) pour utiliser la fonction
 * mail() de PHP
 *
 * Défaut : false
 */

//const SMTP_HOST = false;

/**
 * Port du serveur SMTP
 *
 * 25 = port standard pour connexion non chiffrée (465 pour Gmail)
 * 587 = port standard pour connexion SSL
 *
 * Défaut : 587
 */

//const SMTP_PORT = 587;

/**
 * Login utilisateur pour le server SMTP
 *
 * mettre à null pour utiliser un serveur local ou anonyme
 *
 * Défaut : null
 */

//const SMTP_USER = 'garradin@monserveur.com';

/**
 * Mot de passe pour le serveur SMTP
 *
 * mettre à null pour utiliser un serveur local ou anonyme
 *
 * Défaut : null
 */

//const SMTP_PASSWORD = 'abcd';

/**
 * Sécurité du serveur SMTP
 *
 * NONE = pas de chiffrement
 * SSL = connexion SSL native
 * TLS = connexion TLS native (le plus sécurisé)
 * STARTTLS = utilisation de STARTTLS (moyennement sécurisé)
 *
 * Défaut : STARTTLS
 */

//const SMTP_SECURITY = 'STARTTLS';

/**
 * Adresse e-mail destinée à recevoir les erreurs de mail
 * (adresses invalides etc.)
 *
 * Si laissé NULL, alors l'adresse email de l'association sera utilisée.
 * En cas d'hébergement de plusieurs associations, il est conseillé
 * d'utiliser une adresse par association.
 *
 * Voir la documentation de configuration sur des exemples de scripts
 * permettant de traiter les mails reçus à cette adresse.
 *
 * Défaut : null
 */

//const MAIL_RETURN_PATH = 'returns@monserveur.com';

/**
 * Mot de passe pour l'accès à l'API permettant de gérer les mails d'erreur
 * (voir MAIL_RETURN_PATH)
 *
 * Cette adresse HTTP permet de gérer un bounce email reçu en POST.
 * C'est utile si votre serveur de mail est capable de faire une requête HTTP
 * à la réception d'un message.
 *
 * La requête bounce doit contenir un paramètre "message", contenant l'intégralité
 * de l'email avec les entêtes.
 *
 * Si on définit 'abcd' ici, il faudra faire une requête comme ceci :
 * curl -F 'message=@/tmp/message.eml' https://bounce:abcd@monasso.com/admin/handle_bounce.php
 *
 * En alternative le serveur de mail peut aussi appeler le script
 * 'scripts/handle_bounce.php'
 *
 * Défaut : null (l'API handlebounce est désactivée)
 *
 * @type string|null
 */

//const MAIL_BOUNCE_PASSWORD = null;

/**
 * Couleur primaire de l'interface admin par défaut
 * (peut être personnalisée dans la configuration)
 *
 * Défaut : #20787a
 */

//const ADMIN_COLOR1 = '#20787a';

/**
 * Couleur secondaire de l'interface admin
 * Défaut : #85b9ba
 */

//const ADMIN_COLOR2 = '#85b9ba';

/**
 * Image de fond par défaut de l'interface admin
 *
 * Cette URL doit être absolue (http://...) ou relative à l'admin (/admin/static...)
 *
 * Attention si l'image est sur un domaine différent vous devrez activer l'entête CORS:
 * Access-Control-Allow-Origin "*"
 *
 * sinon la personnalisation des couleurs ne fonctionnera pas
 *
 * Défaut : [ADMIN_URL]static/bg.png
 */

//const ADMIN_BACKGROUND_IMAGE = 'http://mon-asso.fr/fond_garradin.png';


/**
 * Stockage des fichiers
 *
 * Indiquer ici le nom d'une classe de stockage de fichiers
 * (parmis celles disponibles dans lib/Garradin/Files/Backend)
 *
 * Indiquer NULL si vous souhaitez stocker les fichier dans la base
 * de données SQLite (valeur par défaut).
 *
 * Classes de stockage possibles :
 * - SQLite : enregistre dans la base de données (défaut)
 * - FileSystem : enregistrement des fichiers dans le système de fichier
 *
 * ATTENTION : activer FileSystem ET ne pas utiliser de sous-domaine (vhost dédié)
 * ferait courir de graves risques de piratage à votre serveur web si vous ne protégez
 * pas correctement le répertoire de stockage des fichiers !
 *
 * Défaut : null
 */

//const FILE_STORAGE_BACKEND = null;

/**
 * Configuration du stockage des fichiers
 *
 * Indiquer dans cette constante la configuration de la classe de stockage
 * des fichiers.
 *
 * Valeurs possibles :
 * - SQLite : aucune configuration possible
 * - FileSystem : (string) chemin du répertoire où doivent être stockés les fichiers
 *
 * Pour migrer d'un stockage de fichiers à l'autre,
 * voir Configuration > Avancé (accessible uniquement si ENABLE_TECH_DETAILS est à true)
 *
 * Défaut : null
 */

//const FILE_STORAGE_CONFIG = null;

/**
 * Forcer le quota disponible pour les fichiers
 *
 * Si cette constante est renseignée (en octets) alors il ne sera
 * pas possible de stocker plus que cette valeur.
 * Tout envoi de fichier sera refusé.
 *
 * Défaut : null (dans ce cas c'est le stockage qui détermine la taille disponible, donc généralement l'espace dispo sur le disque dur !)
 */

//const FILE_STORAGE_QUOTA = 10*1024*1024; // Forcer le quota alloué à 10 Mo, quel que soit le backend de stockage

/**
 * PDF_COMMAND
 * Commande de création de PDF
 *
 * Commande qui sera exécutée pour créer un fichier PDF à partir d'un HTML.
 * Si laissé non spécifié (ou NULL), Garradin essaiera de détecter une solution entre
 * PrinceXML, Chromium, wkhtmltopdf ou weasyprint. Si aucune solution n'est disponible,
 * une erreur sera levée.
 *
 * %1$s sera remplacé par le chemin du fichier HTML, et %2$s par le chemin du fichier PDF.
 *
 * Exemple : chromium --headless --print-to-pdf=%2$s %1$s
 *
 * Il est aussi possible de simplement spécifier le nom du programme à utiliser,
 * et Garradin placera les arguments automatiquement : prince, chromium, wkhtmltopdf ou weasyprint.
 *
 * Si vous utilisez Prince, un message mentionnant l'utilisation de Prince
 * sera joint aux e-mails utilisant des fichiers PDF, conformément à la licence :
 * https://www.princexml.com/purchase/license_faq/#non-commercial
 *
 * Défaut : null
 */
//const PDF_COMMAND = 'wkhtmltopdf -q --print-media-type --enable-local-file-access %s %s';

/**
 * CALC_CONVERT_COMMAND
 * Outil de conversion de formats de tableur vers un format propriétaire
 *
 * Garradin gère nativement les exports en ODS (OpenDocument : LibreOffice)
 * et CSV, et imports en CSV.
 *
 * En indiquant ici le nom d'un outil, Garradin autorisera aussi
 * l'import en XLSX, XLS et ODS, et l'export en XLSX.
 *
 * Pour cela il procédera simplement à une conversion entre les formats natifs
 * ODS/CSV et XLSX ou XLS.
 *
 * Noter qu'installer ces commandes peut introduire des risques de sécurité sur le serveur.
 *
 * Les outils supportés sont :
 * - ssconvert (apt install gnumeric) (plus rapide)
 * - unoconv (apt install unoconv) (utilise LibreOffice)
 * - unoconvert (https://github.com/unoconv/unoserver/) en spécifiant l'interface
 *
 * Défault : null (= fonctionnalité désactivée)
 */
//const CALC_CONVERT_COMMAND = 'unoconv';
//const CALC_CONVERT_COMMAND = 'ssconvert';
//const CALC_CONVERT_COMMAND = 'unoconvert --interface localhost --port 2022';

/**
 * API_USER et API_PASSWORD
 * Login et mot de passe système de l'API
 *
 * Une API est disponible via l'URL https://login:password@garradin.association.tld/api/...
 * Voir https://fossil.kd2.org/garradin/wiki?name=API pour la documentation
 *
 * Ces deux constantes permettent d'indiquer un nom d'utilisateur
 * et un mot de passe pour accès à l'API.
 *
 * Cet utilisateur est distinct de ceux définis dans la page de gestion des
 * identifiants d'accès à l'API, et aura accès à TOUT en écriture/administration.
 *
 * Défaut: null
 */
//const API_USER = 'coraline';
//const API_PASSWORD = 'thisIsASecretPassword42';

/**
 * DISABLE_INSTALL_PING
 *
 * Lors de l'installation, ou d'une mise à jour, la version installée de Garradin,
 * ainsi que celle de PHP et de SQLite, sont envoyées à Garradin.eu.
 *
 * Cela permet de savoir quelles sont les versions utilisées, et également de compter
 * le nombre d'installations effectuées.
 *
 * Aucune donnée personnelle n'est envoyée. Un identifiant anonyme est envoyé,
 * permettant d'identifier l'installation et éviter les doublons.
 * (voir le code dans lib/.../Install.php)
 *
 * Le code de stockage des statistiques est visible à :
 * https://garradin.eu/ping/
 *
 * Pour désactiver cet envoi il suffit de placer cette constante à TRUE.
 *
 * Défaut : false
 */
//const DISABLE_INSTALL_PING = false;

/**
 * Clé de licence
 *
 * Cette clé permet de débloquer certaines fonctionnalités dans des extensions officielles.
 *
 * Pour l'obtenir il faut se créer un compte sur Garradin.eu
 * et faire une contribution financière.
 * La clé apparaîtra ensuite en dessous des informations
 * de l'association dans la page "Mon abonnement Garradin.eu".
 *
 * Il faut recopier cette clé dans le fichier config.local.php
 * dans la constante CONTRIBUTOR_LICENSE.
 *
 * Merci de ne pas essayer de contourner cette licence et de contribuer au
 * financement de notre travail :-)
 */
//const CONTRIBUTOR_LICENSE = 'XXXXX';

/**
 * Ligne légale sur le pied de page du site public
 *
 * Ce texte (HTML) est affiché en bas des pages du site public.
 * Utile pour indiquer les mentions légales obligatoires
 * Le %1$s est remplacé par le nom de l'association, %2$s par son adresse.
 *
 * Défaut : "Hébergé par nom_association, adresse_association"
 */
//const LEGAL_LINE = 'Hébergé par <strong>%1$s</strong>, %2$s';
