<?php
namespace Garradin;

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
    version_compare(phpversion(), '5.4', '>='),
    'PHP 5.4 ou supérieur requis. PHP version ' . phpversion() . ' installée.'
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
    file_exists(__DIR__ . '/../../include/lib/Template_Lite/class.template.php'),
    'Librairie Template_Lite non disponible.'
);

test_requis(
    file_exists(__DIR__ . '/../../include/lib/KD2'),
    'Librairie KD2 non disponible.'
);

const INSTALL_PROCESS = true;

require_once __DIR__ . '/../../include/init.php';

// Vérifier que les répertoires vides existent, sinon les créer
$paths = [DATA_ROOT, PLUGINS_ROOT, CACHE_ROOT, CACHE_ROOT . '/static', CACHE_ROOT . '/compiled'];

foreach ($paths as $path)
{
    if (!file_exists($path))
        mkdir($path);

    test_requis(
        file_exists($path) && is_dir($path),
        'Le répertoire '.$path.' n\'existe pas ou n\'est pas un répertoire.'
    );

    // On en profite pour vérifier qu'on peut y lire et écrire
    test_requis(
        is_writable($path) && is_readable($path),
        'Le répertoire '.$path.' n\'est pas accessible en lecture/écriture.'
    );
}

if (!file_exists(DB_FILE))
{
    // Renommage du fichier sqlite à la version 0.5.0
    $old_file = str_replace('.sqlite', '.db', DB_FILE);

    if (file_exists($old_file))
    {
        rename($old_file, DB_FILE);
        Utils::redirect('/admin/upgrade.php');
    }
}

$tpl = Template::getInstance();

$tpl->assign('admin_url', WWW_URL . 'admin/');

if (file_exists(DB_FILE))
{
    $tpl->assign('disabled', true);
}
else
{
    $tpl->assign('disabled', false);
    $error = false;

    if (!empty($_POST['save']))
    {
        if (!Utils::CSRF_check('install'))
        {
            $error = 'Une erreur est survenue, merci de renvoyer le formulaire.';
        }
        elseif (Utils::post('passe_membre') != Utils::post('repasse_membre'))
        {
            $error = 'La vérification ne correspond pas au mot de passe.';
        }
        else
        {
            try {
            	Install::install(Utils::post('nom_asso'), Utils::post('adresse_asso'), Utils::post('email_asso'),
            		Utils::post('cat_membre'), Utils::post('nom_membre'), Utils::post('email_membre'), Utils::post('passe_membre'),
            		WWW_URL);

            	Utils::redirect('/admin/login.php');
            }
            catch (UserException $e)
            {
                @unlink(DB_FILE);

                $error = $e->getMessage();
            }
        }
    }

    $tpl->assign('error', $error);
}

$tpl->assign('passphrase', Utils::suggestPassword());
$tpl->display('admin/install.tpl');
