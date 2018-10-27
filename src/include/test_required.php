<?php

/*
 * Tests : vérification que les conditions pour s'exécuter sont remplies
 */

function test_requis($condition, $message)
{
    if ($condition)
    {
        return true;
    }

    if (PHP_SAPI != 'cli')
    {
        header('Content-Type: text/html; charset=utf-8');
        echo "<!DOCTYPE html>\n<html>\n<head>\n<title>Erreur</title>\n<meta charset=\"utf-8\" />\n";
        echo '<style type="text/css">body { font-family: sans-serif; } ';
        echo '.error { color: darkred; padding: .5em; margin: 1em; border: 3px double red; background: yellow; }</style>';
        echo "\n</head>\n<body>\n<h2>Erreur</h2>\n<h3>Le problème suivant empêche Garradin de fonctionner :</h3>\n";
        echo '<p class="error">' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</p>';
        echo '<hr /><p>Pour plus d\'informations consulter ';
        echo '<a href="http://dev.kd2.org/garradin/Probl%C3%A8mes%20fr%C3%A9quents">l\'aide sur les problèmes à l\'installation</a>.</p>';
        echo "\n</body>\n</html>";
    }
    else
    {
        echo "[ERREUR] Le problème suivant empêche Garradin de fonctionner :\n";
        echo $message . "\n";
        echo "Pour plus d'informations consulter http://dev.kd2.org/garradin/Probl%C3%A8mes%20fr%C3%A9quents\n";
    }

    exit;
}

test_requis(
    version_compare(phpversion(), '5.6', '>='),
    'PHP 5.6 ou supérieur requis. PHP version ' . phpversion() . ' installée.'
);

test_requis(
    defined('CRYPT_BLOWFISH') && CRYPT_BLOWFISH,
    'L\'algorithme de hashage de mot de passe Blowfish n\'est pas présent (pas installé ou pas compilé).'
);

test_requis(
    class_exists('SQLite3'),
    'Le module de base de données SQLite3 n\'est pas disponible.'
);

$v = \SQLite3::version();

test_requis(
    version_compare($v['versionString'], '3.7.4', '>='),
    'SQLite3 version 3.7.4 ou supérieur requise. Version installée : ' . $v['versionString']
);

test_requis(
    file_exists(__DIR__ . '/lib/KD2'),
    'Librairie KD2 non disponible.'
);
