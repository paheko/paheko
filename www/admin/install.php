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
        =>  !file_exists(__DIR__ . '/../../include/template_lite/class.template.php'),
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

define('GARRADIN_INSTALL_PROCESS', true);

require __DIR__ . '/../../include/init.php';
require GARRADIN_ROOT . '/include/template.php';

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
                require_once GARRADIN_ROOT . '/include/class.db.php';
                require_once GARRADIN_ROOT . '/include/class.config.php';

                $config = Garradin_Config::getInstance();
                $config->set('nom_asso', utils::post('nom_asso'));
                $config->set('adresse_asso', utils::post('adresse_asso'));
                $config->set('email_asso', utils::post('email_asso'));
                $config->set('site_asso', utils::post('site_asso'));
                $config->set('email_envoi_automatique', utils::post('email_asso'));
                $config->set('champs_obligatoires', array('passe', 'email', 'nom'));

                require_once GARRADIN_ROOT . '/include/class.membres_categories.php';

                $cats = new Garradin_Membres_Categories;
                $id = $cats->add(array(
                    'nom' => 'Membres actifs',
                    'montant_cotisation' => 10));
                $cats->setAccess($id,
                    Garradin_Membres::DROIT_CONNEXION,
                    Garradin_Membres::DROIT_WIKI_LIRE,
                    Garradin_Membres::DROIT_WIKI_ECRIRE);
                $config->set('categorie_membres', $id);

                $id = $cats->add(array(
                    'nom' => ucfirst(utils::post('cat_membre')),
                    'montant_cotisation' => 0));
                $cats->setAccess($id,
                    Garradin_Membres::DROIT_CONNEXION,
                    Garradin_Membres::DROIT_WIKI_ADMIN,
                    Garradin_Membres::DROIT_MEMBRE_ADMIN,
                    Garradin_Membres::DROIT_COMPTA_ADMIN);

                $membres = new Garradin_Membres;
                $membres->add(array(
                    'id_categorie'  =>  $id,
                    'nom'           =>  utils::post('nom_membre'),
                    'email'         =>  utils::post('email_membre'),
                    'passe'         =>  utils::post('passe_membre'),
                    'telephone'     =>  '',
                    'code_postal'   =>  '',
                    'adresse'       =>  '',
                    'ville'         =>  '',
                    'pays'          =>  '',
                    'date_anniversaire' => '',
                    'details'       =>  '',
                ));

                $config->save();
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

$tpl->display('install.tpl');

?>