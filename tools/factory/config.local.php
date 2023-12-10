<?php

namespace Paheko;

/**
 * Ce fichier permet de configurer Paheko pour une utilisation
 * avec plusieurs associations, mais une seule copie du code source.
 * (aussi appelé installation multi-sites, ferme ou usine)
 *
 * Voir la doc : https://fossil.kd2.org/paheko/wiki?name=Multi-sites
 *
 * N'oubliez pas d'installer également le script cron.sh fournit
 * pour lancer les rappels automatiques et sauvegardes.
 *
 * Si cela ne suffit pas à vos besoins, contactez-nous :
 * https://paheko.cloud/contact
 * pour une aide spécifique à votre installation.
 */

// Décommenter cette ligne si vous n'utilisez pas NFS,
// pour rendre les bases de données plus rapides.
//
// Si vous utilisez NFS, décommenter cette ligne risque
// de provoquer des corruptions de base de données !
#const SQLITE_JOURNAL_MODE = 'WAL';

// Nom de domaine parent des associations hébergées
// Exemple : si vos associations sont hébergées en clubdetennis.paheko.cloud,
// indiquer ici 'paheko.cloud'
const FACTORY_DOMAIN = 'monsite.tld';

// Répertoire où seront stockées les données des utilisateurs
// Dans ce répertoire, un sous-répertoire sera créé pour chaque compte
// Ainsi 'clubdetennis.paheko.cloud' sera dans le répertoire courant (__DIR__),
// sous-répertoire 'users' et dans celui-ci, sous-répertoire 'clubdetennis'
//
// Pour chaque utilisateur il faudra créer le sous-répertoire en premier lieu
// (eg. mkdir .../users/clubdetennis)
const FACTORY_USER_DIRECTORY = __DIR__ . '/users';

// Envoyer les erreurs PHP par mail à l'adresse de l'administrateur système
// (mettre à null pour ne pas recevoir d'erreurs)
const MAIL_ERRORS = 'administrateur@monsite.tld';

// IMPORTANT !
// Modifier pour indiquer une valeur aléatoire de plus de 30 caractères
const SECRET_KEY = 'Indiquer ici une valeur aléatoire !';

// Quota de stockage de documents (en octets)
// Définit la taille de stockage disponible pour chaque association pour ses documents
const FILE_STORAGE_QUOTA = 1 * 1024 * 1024 * 1024; // 1 Go

////////////////////////////////////////////////////////////////
// Réglages conseillés, normalement il n'y a rien à modifier ici

// Indiquer que l'on va utiliser cron pour lancer les tâches à exécuter (envoi de rappels de cotisation)
const USE_CRON = true;

// Cache partagé
const SHARED_CACHE_ROOT = __DIR__ . '/cache';

// Désactiver le log des erreurs PHP visible dans l'interface (sécurité)
const ENABLE_TECH_DETAILS = false;

// Désactiver les mises à jour depuis l'interface web
// Pour être sûr que seul l'admin sys puisse faire des mises à jour
const ENABLE_UPGRADES = false;

// Ne pas afficher les erreurs de code PHP
const SHOW_ERRORS = false;

////////////////////////////////////////////////////////////////
// Code 'magique' qui va configurer Paheko selon les réglages

$login = null;

// Un sous-domaine ne peut pas faire plus de 63 caractères
$login_regexp = '([a-z0-9_-]{1,63})';
$domain_regexp = sprintf('/^%s\.%s$/', $login_regexp, preg_quote(FACTORY_DOMAIN, '/'));

if (isset($_SERVER['SERVER_NAME']) && preg_match($domain_regexp, $_SERVER['SERVER_NAME'], $match)) {
	$login = $match[1];
}
elseif (PHP_SAPI == 'cli' && !empty($_SERVER['PAHEKO_FACTORY_USER']) && preg_match('/^' . $login_regexp . '$/', $_SERVER['PAHEKO_FACTORY_USER'])) {
	$login = $_SERVER['PAHEKO_FACTORY_USER'];
}
else {
	// Login invalide ou non fourni
	http_response_code(404);
	die('<h1>Page non trouvée</h1>');
}

$user_data_dir = rtrim(FACTORY_USER_DIRECTORY, '/') . '/' . $login;

if (!is_dir($user_data_dir)) {
	http_response_code(404);
	die("<h1>Cette association n'existe pas.</h1>");
}

// Définir le dossier où sont stockés les données
define('Paheko\DATA_ROOT', $user_data_dir);

// Définir l'URL
define('Paheko\WWW_URL', 'https://' . $login . '.' . FACTORY_DOMAIN . '/');
define('Paheko\WWW_URI', '/');
