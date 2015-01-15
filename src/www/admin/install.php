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
$paths = [CACHE_ROOT, CACHE_ROOT . '/static', CACHE_ROOT . '/compiled'];

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
                $db = DB::getInstance(true);

                // Création de la base de données
                $db->exec('BEGIN;');
                $db->exec(file_get_contents(DB_SCHEMA));
                $db->exec('END;');

                // Configuration de base
                $config = Config::getInstance();
                $config->set('nom_asso', Utils::post('nom_asso'));
                $config->set('adresse_asso', Utils::post('adresse_asso'));
                $config->set('email_asso', Utils::post('email_asso'));
                $config->set('site_asso', WWW_URL);
                $config->set('monnaie', '€');
                $config->set('pays', 'FR');
                $config->set('email_envoi_automatique', Utils::post('email_asso'));
                $config->setVersion(garradin_version());

                $champs = Membres\Champs::importInstall();
                $champs->save(false); // Pas de copie car pas de table membres existante

                $config->set('champ_identifiant', 'email');
                $config->set('champ_identite', 'nom');
                
                // Création catégories
                $cats = new Membres\Categories;
                $id = $cats->add([
                    'nom' => 'Membres actifs',
                ]);
                $config->set('categorie_membres', $id);

                $id = $cats->add([
                    'nom' => 'Anciens membres',
                    'droit_inscription' => Membres::DROIT_AUCUN,
                    'droit_wiki' => Membres::DROIT_AUCUN,
                    'droit_membres' => Membres::DROIT_AUCUN,
                    'droit_compta' => Membres::DROIT_AUCUN,
                    'droit_config' => Membres::DROIT_AUCUN,
                    'droit_connexion' => Membres::DROIT_AUCUN,
                    'cacher' => 1,
                ]);

                $id = $cats->add([
                    'nom' => ucfirst(Utils::post('cat_membre')),
                    'droit_inscription' => Membres::DROIT_AUCUN,
                    'droit_wiki' => Membres::DROIT_ADMIN,
                    'droit_membres' => Membres::DROIT_ADMIN,
                    'droit_compta' => Membres::DROIT_ADMIN,
                    'droit_config' => Membres::DROIT_ADMIN,
                ]);

                // Création premier membre
                $membres = new Membres;
                $id_membre = $membres->add([
                    'id_categorie'  =>  $id,
                    'nom'           =>  Utils::post('nom_membre'),
                    'email'         =>  Utils::post('email_membre'),
                    'passe'         =>  Utils::post('passe_membre'),
                    'pays'          =>  'FR',
                ]);

                // Création wiki
                $page = Wiki::transformTitleToURI(Utils::post('nom_asso'));
                $config->set('accueil_wiki', $page);
                $wiki = new Wiki;
                $id_page = $wiki->create([
                    'titre' =>  Utils::post('nom_asso'),
                    'uri'   =>  $page,
                ]);

                $wiki->editRevision($id_page, 0, [
                    'id_auteur' =>  $id_membre,
                    'contenu'   =>  "Bienvenue dans le wiki de ".Utils::post('nom_asso')." !\n\nCliquez sur le bouton « éditer » pour modifier cette page.",
                ]);

                // Création page wiki connexion
                $page = Wiki::transformTitleToURI('Bienvenue');
                $config->set('accueil_connexion', $page);
                $id_page = $wiki->create([
                    'titre' =>  'Bienvenue',
                    'uri'   =>  $page,
                ]);

                $wiki->editRevision($id_page, 0, [
                    'id_auteur' =>  $id_membre,
                    'contenu'   =>  "Bienvenue dans l'administration de ".Utils::post('nom_asso')." !\n\n"
                        .   "Utilisez le menu à gauche pour accéder aux différentes rubriques.",
                ]);

                // Mise en place compta
                $comptes = new Compta\Comptes;
                $comptes->importPlan();

                $comptes = new Compta\Categories;
                $comptes->importCategories();

                $ex = new Compta\Exercices;
                $ex->add([
                    'libelle'   =>  'Premier exercice',
                    'debut'     =>  date('Y-01-01'),
                    'fin'       =>  date('Y-12-31')
                ]);

                $config->save();

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
