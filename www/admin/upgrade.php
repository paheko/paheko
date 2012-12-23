<?php
namespace Garradin;

define('GARRADIN_UPGRADE_PROCESS', true);

require_once __DIR__ . '/../../include/init.php';

if (!file_exists(GARRADIN_DB_FILE))
{
    utils::redirect('/admin/install.php');
}

$config = Config::getInstance();

$v = $config->getVersion();

if (version_compare($v, garradin_version(), '>='))
{
    throw new UserException("Pas de mise à jour à faire.");
}

$db = DB::getInstance();
$redirect = true;

echo '<!DOCTYPE html>
<meta charset="utf-8" />
<h3>Mise à jour de Garradin '.$config->getVersion().' vers la version '.garradin_version().'...</h3>';

flush();

// versions pré-0.3.0
if (!$v)
{
    $db->exec('ALTER TABLE membres ADD COLUMN lettre_infos INTEGER DEFAULT 0;');
    $v = '0.3.0';
}

if (version_compare($v, '0.4.0', '<'))
{
    $config->set('monnaie', '€');
    $config->set('pays', 'FR');
    $config->save();

    $db->exec(file_get_contents(GARRADIN_ROOT . '/include/data/0.4.0.sql'));

    // Mise en place compta
    $comptes = new Compta_Comptes;
    $comptes->importPlan();

    $comptes = new Compta_Categories;
    $comptes->importCategories();
}

if (version_compare($v, '0.4.3', '<'))
{
    $db->exec(file_get_contents(GARRADIN_ROOT . '/include/data/0.4.3.sql'));
}

if (version_compare($v, '0.4.5', '<'))
{
    // Mise à jour plan comptable
    $comptes = new Compta_Comptes;
    $comptes->importPlan();

    // Création page wiki connexion
    $wiki = new Wiki;
    $page = Wiki::transformTitleToURI('Bienvenue');
    $config->set('accueil_connexion', $page);

    if (!$wiki->getByUri($page))
    {
        $id_page = $wiki->create(array(
            'titre' =>  'Bienvenue',
            'uri'   =>  $page,
        ));

        $wiki->editRevision($id_page, 0, array(
            'contenu'   =>  "Bienvenue dans l'administration de ".$config->get('nom_asso')." !\n\n"
                .   "Utilisez le menu à gauche pour accéder aux différentes rubriques.",
        ));
    }

    $config->set('accueil_connexion', $page);
    $config->save();
}

if (version_compare($v, '0.5.0', '<'))
{
    // Récupération de l'ancienne config
    $champs_modifiables_membre = $db->querySingle('SELECT valeur FROM config WHERE cle = "champs_modifiables_membre";');
    $champs_modifiables_membre = explode($champs_modifiables_membre);

    $champs_obligatoires = $db->querySingle('SELECT valeur FROM config WHERE cle = "champs_obligatoires";');
    $champs_obligatoires = explode($champs_obligatoires);

    // Application à la nouvelle config (TODO)

    // Suppression de l'ancienne config
    $db->exec('DELETE FROM config WHERE cle IN ("champs_obligatoires", "champs_modifiables_membre");');
}

utils::clearCaches();

$config->setVersion(garradin_version());

echo '<h4>Mise à jour terminée.</h4>
<p><a href="'.WWW_URL.'admin/">Retour</a></p>';

if ($redirect)
{
    echo '
    <script type="text/javascript">
    window.setTimeout(function () { 
        window.location.href = "'.WWW_URL.'admin/"; 
    }, 1000);
    </script>';
}

?>