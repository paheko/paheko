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
 * Se connecter automatiquement avec l'ID de membre indiqué
 * Exemple: LOCAL_LOGIN = 42 connectera automatiquement le membre n°42
 * Attention à ne pas utiliser en production !
 *
 * Il est aussi possible de mettre "LOCAL_LOGIN = -1" pour se connecter
 * avec le premier membre trouvé qui peut gérer la configuration (et donc
 * modifier les droits des membres).
 *
 * Défault : false (connexion automatique désactivée)
 */

//const LOCAL_LOGIN = false;

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
 * Doit-on suggérer à l'utilisateur d'utiliser la version chiffrée du site ?
 *
 * 1 ou true = affiche un message de suggestion sur l'écran de connexion invitant à utiliser le site chiffré
 * (conseillé si vous avez un certificat auto-signé ou peu connu type CACert)
 * 2 = rediriger automatiquement sur la version chiffrée pour l'administration (mais pas le site public)
 * 3 = rediriger automatiquement sur la version chiffrée pour administration et site public
 * false ou 0 = aucune version chiffrée disponible, donc ne rien proposer ni rediriger
 *
 * Défaut : false
 */

//const PREFER_HTTPS = false;

/**
 * Répertoire où se situe le code source de Garradin
 *
 * Défaut : répertoire racine de Garradin (__DIR__)
 */

//const ROOT = __DIR__;

/**
 * Répertoire où sont situées les données de Garradin
 * (incluant la base de données SQLite, les sauvegardes, le cache et les fichiers locaux)
 *
 * Défaut : identique à ROOT
 */

//const DATA_ROOT = ROOT;

/**
 * Répertoire où est situé le cache (fichiers temporaires utilisés pour accélérer le chargement des pages)
 *
 * Défaut : sous-répertoire 'cache' de DATA_ROOT
 */

//const CACHE_ROOT = ROOT . '/cache';

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
 * Plugins fixes qui ne peuvent être désinstallés par l'utilisateur
 * (séparés par une virgule)
 *
 * Ils seront aussi réinstallés en cas de restauration de sauvegarde,
 * s'ils ne sont pas dans la sauvegarde.
 *
 * Exemple : PLUGINS_SYSTEM = 'gestion_emails,factures'
 *
 * Défaut : aucun (chaîne vide)
 */

//const PLUGINS_SYSTEM = '';

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
 * Envoi des erreurs à une API compatible AirBrake/Errbit
 *
 * Si renseigné avec une URL HTTP(S) valide, chaque erreur système sera envoyée
 * automatiquement à cette URL.
 *
 * Si laissé à null, aucun rapport ne sera envoyé.
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
 * - vérification de nouvelle version
 *
 * Ces infos ne sont visibles que par les membres ayant accès à la configuration.
 *
 * Défaut : true
 * (Afin d'aider au rapport de bugs des instances auto-hébergées)
 */

//const ENABLE_TECH_DETAILS = true;

/**
 * Utilisation de cron pour les tâches automatiques
 *
 * Si "true" on s'attend à ce qu'une tâche automatisée appelle
 * le script cron.php dans le répertoire "scripts" toutes les 24 heures.
 * Sinon Garradin effectuera les actions automatiques quand quelqu'un
 * se connecte à l'administration ou visite le site.
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
 * Activer les sauvegardes automatiques
 *
 * Utile à désactiver si vous avez déjà des sauvegardes effectuées
 * automatiquement au niveau du système.
 *
 * Sinon les sauvegardes seront effectuées soit par la tâche cron
 * soit à l'affichage de la page d'accueil (si nécessaire).
 *
 * Voir paramètre USE_CRON aussi
 *
 * Défaut : true
 */

//const ENABLE_AUTOMATIC_BACKUPS = true;


/**
 * Couleur primaire de l'interface admin par défaut
 * (peut être personnalisée dans la configuration)
 *
 * Défaut : #9c4f15
 */

//const ADMIN_COLOR1 = '#20787a';

/**
 * Couleur secondaire de l'interface admin
 * Défaut : #d98628
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
 * Défaut : [ADMIN_URL]static/gdin_bg.png
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
 * - FileSystem : 'string' = chemin du répertoire où doivent être stockés les fichiers,
 * %s doit être ajouté à la fin pour indiquer le répertoire et nom du fichier
 *
 * Défaut : null
 *
 * @vararray
 */

//const FILE_STORAGE_CONFIG = null;

/**
 * Forcer le quota disponible pour les fichiers
 *
 * Si cette constante est renseignée (en octets) alors il ne sera
 * pas possible de stocker plus que cette valeur.
 * Tout envoi de fichier sera refusé.
 *
 * Défaut : null (dans ce cas c'est le stockage qui détermine la taille disponible)
 */

//const FILE_STORAGE_QUOTA = 10000; // Forcer le quota alloué à 10 Mo, quel que soit le backend de stockage
