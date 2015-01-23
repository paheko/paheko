<?php

/**
 * Ce fichier représente un exemple des constantes de configuration
 * disponibles pour Garradin.
 */

// Nécessaire pour situer les constantes dans le bon namespace
namespace Garradin;

// Connexion automatique à l'administration avec l'adresse e-mail donnée
#const LOCAL_LOGIN = 'president@association.net';

// Connexion automatique avec le numéro de membre indiqué
// Défaut : false (connexion automatique désactivée)
const LOCAL_LOGIN = 1;

// Répertoire où est le code source de Garradin
const ROOT = '/usr/share/garradin';

// Répertoire où sont situées les données de Garradin
// (incluant la base de données SQLite, le cache et les fichiers locaux)
// Défaut : le même répertoire que le source
const DATA_ROOT = '/var/www/garradin';

// Emplacement de la base de données
const DB_FILE = '/var/lib/sqlite/garradin.sqlite';

// Adresse URI de la racine du site Garradin
// (doit se terminer par un slash)
// Défaut : découverte automatique à partir de SCRIPT_NAME
const WWW_URI = '/garradin/';

// Adresse URL HTTP(S) de Garradin
// Défaut : découverte à partir de HTTP_HOST ou SERVER_NAME + WWW_URI
define('Garradin\WWW_URL', 'http://garradin.net' . WWW_URI);

// Emplacement de stockage des plugins
define('Garradin\PLUGINS_ROOT', DATA_ROOT . '/plugins');

// Plugins fixes qui ne peuvent être désinstallés (séparés par une virgule)
const PLUGINS_SYSTEM = 'email,web';

// Affichage des erreurs
// Si "true" alors un message expliquant l'erreur et comment rapporter le bug s'affiche
// en cas d'erreur. Sinon rien ne sera affiché.
// Défaut : true
const SHOW_ERRORS = true;

// Envoi des erreurs par e-mail
// Si rempli, un email sera envoyé à l'adresse indiquée à chaque fois qu'une erreur
// d'exécution sera rencontrée.
// Si "false" alors aucun email ne sera envoyé
// Note : les erreurs sont déjà toutes loguées dans error.log à la racine de DATA_ROOT
const MAIL_ERRORS = false;

// Utilisation de cron pour les tâches automatiques
// Si "true" on s'attend à ce qu'une tâche automatisée appelle
// le script cron.php à la racine toutes les 24 heures. Sinon Garradin
// effectuera les actions automatiques quand quelqu'un se connecte à 
// l'administration ou visite le site.
// Défaut : false
const USE_CRON = false;

// Activation de l'envoi de fichier directement par le serveur web.
// Permet d'améliorer la rapidité d'envoi des fichiers.
// Supporte les serveurs web suivants :
// - Apache avec mod_xsendfile (paquet libapache2-mod-xsendfile)
// - Lighttpd
// N'activer que si vous êtes sûr que le module est installé et activé.
// Nginx n'est PAS supporté, car X-Accel-Redirect ne peut gérer que des fichiers
// qui sont *dans* le document root du vhost, ce qui n'est pas le cas ici.
const ENABLE_XSENDFILE = false;