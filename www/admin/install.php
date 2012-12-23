<?php
/*
 * Tests : vérification que les conditions pour s'exécuter sont remplies
 */

$tests = array(
    'La version de PHP installée est inférieure à 5.3 !'
        =>  version_compare(phpversion(), '5.3', '<'),
    'L\'algorithme Blowfish de hashage de mot de passe n\'est pas présent !'
        =>  !defined('CRYPT_BLOWFISH') || !CRYPT_BLOWFISH,
    'Le module de bases de données SQLite3 n\'est pas installé !'
        =>  !class_exists('SQLite3'),
    'La librairie Template_Lite ne semble pas disponible !'
        =>  !file_exists(__DIR__ . '/../../include/libs/template_lite/class.template.php'),
    #'Dummy' => true,
);

$fail = false;

if (PHP_SAPI != 'cli' && array_sum($tests) > 0)
{
    header('Content-Type: text/html; charset=utf-8');
    echo '<pre>';
}

foreach ($tests as $desc=>$fail)
{
    if ($fail)
    {
        echo $desc . "\n";
    }
}

if ($fail)
{
    echo "\n<b>Erreur fatale :</b> Garradin a besoin que la condition mentionnée soit remplie pour s'exécuter.\n";

    if (PHP_SAPI != 'cli')
        echo '</pre>';

    exit;
}

namespace Garradin;

define('GARRADIN_INSTALL_PROCESS', true);

require_once __DIR__ . '/../../include/init.php';
require_once GARRADIN_ROOT . '/include/template.php';

if (file_exists(GARRADIN_DB_FILE))
{
    $tpl->assign('disabled', true);
}
else
{
    $tpl->assign('disabled', false);
    $error = false;

    if (!empty($_POST['save']))
    {
        if (!utils::CSRF_check('install'))
        {
            $error = 'Une erreur est survenue, merci de renvoyer le formulaire.';
        }
        elseif (utils::post('passe_membre') != utils::post('repasse_membre'))
        {
            $error = 'La vérification ne correspond pas au mot de passe.';
        }
        else
        {
            try {
                // Configuration de base
                $config = Config::getInstance();
                $config->set('nom_asso', utils::post('nom_asso'));
                $config->set('adresse_asso', utils::post('adresse_asso'));
                $config->set('email_asso', utils::post('email_asso'));
                $config->set('site_asso', utils::post('site_asso'));
                $config->set('monnaie', '€');
                $config->set('pays', 'FR');
                $config->set('email_envoi_automatique', utils::post('email_asso'));
                $config->setVersion(garradin_version());

                $champs = Champs_Membres::import();
                $champs->save(false); // Pas de copie car pas de table membres existante

                $config->set('champs_membres', $champs);

                // Création catégories
                $cats = new Membres_Categories;
                $id = $cats->add(array(
                    'nom' => 'Membres actifs',
                    'montant_cotisation' => 10));
                $config->set('categorie_membres', $id);

                $id = $cats->add(array(
                    'nom' => 'Anciens membres',
                    'montant_cotisation' => 0,
                    'droit_inscription' => Membres::DROIT_AUCUN,
                    'droit_wiki' => Membres::DROIT_AUCUN,
                    'droit_membres' => Membres::DROIT_AUCUN,
                    'droit_compta' => Membres::DROIT_AUCUN,
                    'droit_config' => Membres::DROIT_AUCUN,
                    'droit_connexion' => Membres::DROIT_AUCUN,
                    'cacher' => 1,
                    ));

                $id = $cats->add(array(
                    'nom' => ucfirst(utils::post('cat_membre')),
                    'montant_cotisation' => 0,
                    'droit_inscription' => Membres::DROIT_AUCUN,
                    'droit_wiki' => Membres::DROIT_ADMIN,
                    'droit_membres' => Membres::DROIT_ADMIN,
                    'droit_compta' => Membres::DROIT_ADMIN,
                    'droit_config' => Membres::DROIT_ADMIN,
                    ));

                // Création premier membre
                $membres = new Membres;
                $id_membre = $membres->add(array(
                    'id_categorie'  =>  $id,
                    'nom'           =>  utils::post('nom_membre'),
                    'email'         =>  utils::post('email_membre'),
                    'passe'         =>  utils::post('passe_membre'),
                    'pays'          =>  'FR',
                ));

                // Création wiki
                $page = Wiki::transformTitleToURI(utils::post('nom_asso'));
                $config->set('accueil_wiki', $page);
                $wiki = new Wiki;
                $id_page = $wiki->create(array(
                    'titre' =>  utils::post('nom_asso'),
                    'uri'   =>  $page,
                ));

                $wiki->editRevision($id_page, 0, array(
                    'id_auteur' =>  $id_membre,
                    'contenu'   =>  "Bienvenue dans le wiki de ".utils::post('nom_asso')." !\n\nCliquez sur le bouton « éditer » pour modifier cette page.",
                ));

                // Création page wiki connexion
                $page = Wiki::transformTitleToURI('Bienvenue');
                $config->set('accueil_connexion', $page);
                $id_page = $wiki->create(array(
                    'titre' =>  'Bienvenue',
                    'uri'   =>  $page,
                ));

                $wiki->editRevision($id_page, 0, array(
                    'id_auteur' =>  $id_membre,
                    'contenu'   =>  "Bienvenue dans l'administration de ".utils::post('nom_asso')." !\n\n"
                        .   "Utilisez le menu à gauche pour accéder aux différentes rubriques.",
                ));                

                // Mise en place compta
                $comptes = new Compta_Comptes;
                $comptes->importPlan();

                $comptes = new Compta_Categories;
                $comptes->importCategories();

                $ex = new Compta_Exercices;
                $ex->add(array('libelle' => 'Premier exercice', 'debut' => date('Y-01-01'), 'fin' => date('Y-12-31')));

                $config->save();

                utils::redirect('/admin/login.php');
            }
            catch (UserException $e)
            {
                @unlink(GARRADIN_DB_FILE);
                $error = $e->getMessage();
            }
        }
    }

    $tpl->assign('error', $error);
}

$tpl->assign('passphrase', utils::suggestPassword());

$tpl->display('admin/install.tpl');

?>