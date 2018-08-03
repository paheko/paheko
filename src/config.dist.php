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
 * Si aucune valeur n'est définie, Garradin ajoutera automatiquement
 * une valeur au hasard dans le fichier config.local.php.
 */
const SECRET_KEY = '3xUhIgGwuovRKOjVsVPQ5yUMfXUSIOX2GKzcebsz5OINrYC50r';

/**
 * Se connecter automatiquement avec l'ID de membre indiqué
 * Exemple: LOCAL_LOGIN = 42
 *
 * Défault : false (connexion automatique désactivée)
 */
const LOCAL_LOGIN = false;

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
const ALLOW_MODIFIED_IMPORT = true;

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
const PREFER_HTTPS = false;

/**
 * Répertoire où se situe le code source de Garradin
 *
 * Défaut : répertoire racine de Garradin (__DIR__)
 */
const ROOT = __DIR__;

/**
 * Répertoire où sont situées les données de Garradin
 * (incluant la base de données SQLite, le cache et les fichiers locaux)
 *
 * Défaut : identique à ROOT
 */
const DATA_ROOT = ROOT;

/**
 * Emplacement du fichier de base de données de Garradin
 *
 * Défaut : ROOT . '/association.sqlite'
 */
const DB_FILE = ROOT . '/association.sqlite';

/**
 * Emplacement de stockage des plugins
 *
 * Défaut : DATA_ROOT . '/plugins'
 */
const PLUGINS_ROOT = DATA_ROOT . '/plugins';

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
const PLUGINS_SYSTEM = '';

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
 * Défaut : true
 */
const SHOW_ERRORS = true;

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
const MAIL_ERRORS = false;

/**
 * Utilisation de cron pour les tâches automatiques
 * 
 * Si "true" on s'attend à ce qu'une tâche automatisée appelle
 * le script cron.php à la racine toutes les 24 heures. Sinon Garradin
 * effectuera les actions automatiques quand quelqu'un se connecte à 
 * l'administration ou visite le site.
 * 
 * Défaut : false
 */
const USE_CRON = false;

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
 * Défaut : false
 */
const ENABLE_XSENDFILE = false;

/**
 * Serveur NTP utilisé pour les connexions avec TOTP
 * (utilisé seulement si le code OTP fourni est faux)
 *
 * Désactiver (false) si vous êtes sûr que votre serveur est toujours à l'heure.
 *
 * Défaut : fr.pool.ntp.org
 */
const NTP_SERVER = 'fr.pool.ntp.org';

/**
 * Hôte du serveur SMTP, mettre à false (défaut) pour utiliser la fonction
 * mail() de PHP
 *
 * Défaut : false
 */
const SMTP_HOST = false;

/**
 * Port du serveur SMTP
 * 
 * 25 = port standard pour connexion non chiffrée (465 pour Gmail)
 * 587 = port standard pour connexion SSL
 *
 * Défaut : 587
 */
const SMTP_PORT = 587;

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
const SMTP_SECURITY = 'STARTTLS';

/**
 * Forcer la valeur de l'expéditeur des emails
 * 
 * false, null ou vide = désactivé
 * chaîne = adresse email qui sera utilisé dans le champ From
 * des emails envoyés
 * 
 * Utile pour les services d'envoi SMTP tiers comme Amazon SES.
 * Si activé le "From" sera : "Nom de l'association" <adresse@email.tld>
 * avec le Reply-To positionné sur l'adresse de l'association
 * 
 * Défaut : false
 */
const FORCE_EMAIL_FROM = false;

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
const ENABLE_AUTOMATIC_BACKUPS = true;
