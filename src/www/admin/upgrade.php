<?php
namespace Garradin;

const UPGRADE_PROCESS = true;

require_once __DIR__ . '/../../include/init.php';

$config = Config::getInstance();

$v = $config->getVersion();

if (version_compare($v, garradin_version(), '>='))
{
    throw new UserException("Pas de mise à jour à faire.");
}

Install::checkAndCreateDirectories();

if (Static_Cache::exists('upgrade'))
{
    $path = Static_Cache::getPath('upgrade');
    throw new UserException('Une mise à jour est déjà en cours.'
        . PHP_EOL . 'Si celle-ci a échouée et que vous voulez ré-essayer, supprimez le fichier suivant:'
        . PHP_EOL . $path);
}

Static_Cache::store('upgrade', 'Mise à jour en cours.');

$db = DB::getInstance();
$redirect = true;

// Créer une sauvegarde automatique
(new Sauvegarde)->create('pre-upgrade-' . garradin_version());

echo '<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, target-densitydpi=device-dpi" />
    <link rel="stylesheet" type="text/css" href="static/admin.css" media="all" />
    <script type="text/javascript" src="static/scripts/loader.js"></script>
    <title>Mise à jour</title>
</head>
<body>
<header class="header">
    <nav class="menu"></nav>
    <h1>Mise à jour de Garradin '.$config->getVersion().' vers la version '.garradin_version().'...</h1>
</header>
<main>
<div id="loader" class="loader" style="margin: 2em 0; height: 50px;"></div>
<script>
animatedLoader(document.getElementById("loader"), 5);
</script>';

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

    $db->exec(file_get_contents(ROOT . '/include/data/0.4.0.sql'));

    // Mise en place compta
    $comptes = new Compta\Comptes;
    $comptes->importPlan();

    $comptes = new Compta\Categories;
    $comptes->importCategories();
}

if (version_compare($v, '0.4.3', '<'))
{
    $db->exec(file_get_contents(ROOT . '/include/data/0.4.3.sql'));
}

if (version_compare($v, '0.4.5', '<'))
{
    // Mise à jour plan comptable
    $comptes = new Compta\Comptes;
    $comptes->importPlan();

    // Création page wiki connexion
    $wiki = new Wiki;
    $page = Wiki::transformTitleToURI('Bienvenue');
    $config->set('accueil_connexion', $page);

    if (!$wiki->getByUri($page))
    {
        $id_page = $wiki->create([
            'titre' =>  'Bienvenue',
            'uri'   =>  $page,
        ]);

        $wiki->editRevision($id_page, 0, [
            'id_auteur' =>  null,
            'contenu'   =>  "Bienvenue dans l'administration de ".$config->get('nom_asso')." !\n\n"
                .   "Utilisez le menu à gauche pour accéder aux différentes rubriques.",
        ]);
    }

    $config->set('accueil_connexion', $page);
    $config->save();
}

if (version_compare($v, '0.5.0', '<'))
{
    // Récupération de l'ancienne config
    $champs_modifiables_membre = $db->firstColumn('SELECT valeur FROM config WHERE cle = "champs_modifiables_membre";');
    $champs_modifiables_membre = !empty($champs_modifiables_membre) ? explode(',', $champs_modifiables_membre) : [];

    $champs_obligatoires = $db->firstColumn('SELECT valeur FROM config WHERE cle = "champs_obligatoires";');
    $champs_obligatoires = !empty($champs_obligatoires) ? explode(',', $champs_obligatoires) : [];

    // Import des champs membres par défaut
    $champs = Membres\Champs::importInstall();

    // Application de l'ancienne config aux nouveaux champs membres
    foreach ($champs_obligatoires as $name)
    {
        if ($champs->get($name) !== null)
            $champs->set($name, 'mandatory', true);
    }

    foreach ($champs_modifiables_membre as $name)
    {
        if ($champs->get($name) !== null)
            $champs->set($name, 'editable', true);
    }

    $champs->save();

    $config->set('champs_membres', $champs);
    $config->save();

    // Suppression de l'ancienne config
    $db->exec('DELETE FROM config WHERE cle IN ("champs_obligatoires", "champs_modifiables_membre");');
}

if (version_compare($v, '0.6.0-rc1', '<'))
{
    $categories = new Membres\Categories;
    $list = $categories->listComplete();

    $db->exec('PRAGMA foreign_keys = OFF; BEGIN;');

    // Mise à jour base de données
    $db->exec(file_get_contents(ROOT . '/include/data/0.6.0.sql'));

    $id_cat_cotisation = $db->firstColumn('SELECT id FROM compta_categories WHERE compte = 756 LIMIT 1;');

    // Conversion des cotisations de catégories en cotisations indépendantes
    foreach ($list as $cat)
    {
        $db->insert('cotisations', [
            'id_categorie_compta'   =>  null,
            'intitule'              =>  $cat->nom,
            'montant'               =>  (float) $cat->montant_cotisation,
            // Convertir un nombre de mois en nombre de jours
            'duree'                 =>  round($cat->duree_cotisation * 30.44),
            'description'           =>  'Créé automatiquement depuis les catégories de membres (version 0.5.x)',
        ]);

        $args = [
            'id_cotisation' =>  (int)$db->lastInsertRowId(),
            'id_categorie'  =>  (int)$cat->id,
        ];

        // import des dates de cotisation existantes comme paiements
        $db->preparedQuery('INSERT INTO cotisations_membres 
            (id_membre, id_cotisation, date)
            SELECT id, :id_cotisation, date(date_cotisation) FROM membres
            WHERE date_cotisation IS NOT NULL AND date_cotisation != \'\' AND id_categorie = :id_categorie;',
            $args);

        // Mais on ne crée pas d'écriture comptable, car elles existent probablement déjà
    }

    // Déplacement des squelettes dans le répertoire public
    if (!file_exists(ROOT . '/www/squelettes'))
    {
        mkdir(ROOT . '/www/squelettes');
    }

    if (file_exists(ROOT . '/squelettes'))
    {
        $dir = dir(ROOT . '/squelettes');

        while ($file = $dir->read())
        {
            if ($file == '.' || $file == '..')
                continue;

            rename(ROOT . '/squelettes/' . $file, ROOT . '/www/squelettes/' . $file);
        }

        $dir->close();

        @rmdir(ROOT . '/squelettes');
    }

    $db->exec('END; PRAGMA foreign_keys = ON;');

    // Mise à jour de la table membres, suppression du champ date_cotisation notamment
    $config->get('champs_membres')->save();

    // Possibilité de choisir l'identité et l'identifiant d'un membre
    $config->set('champ_identite', 'nom');
    $config->set('champ_identifiant', 'email');
    $config->save();
}

if (version_compare($v, '0.7.0', '<'))
{
    $db->exec('PRAGMA foreign_keys = OFF; BEGIN;');

    // Mise à jour base de données
    $db->exec(file_get_contents(ROOT . '/include/data/0.7.0.sql'));

    // Changement de syntaxe du Wiki vers SkrivML
    $wiki = new Wiki;
    $res = $db->get('SELECT id_page, contenu, revision, chiffrement FROM wiki_revisions GROUP BY id_page ORDER BY revision DESC;');

    foreach ($res as $row)
    {
        // Ne pas convertir le contenu chiffré, de toute évidence
        if ($row->chiffrement)
            continue;

        $content = $row->contenu;
        $content = Utils::HTMLToSkriv($content);
        $content = Utils::SpipToSkriv($content);

        if ($content != $row->contenu)
        {
            $wiki->editRevision($row->id_page, $row->revision, [
                'id_auteur'     =>  null,
                'contenu'       =>  $content,
                'modification'  =>  'Mise à jour 0.7.0 (transformation SPIP vers SkrivML)',
            ]);
        }
    }

    $db->exec('END;');
}

if (version_compare($v, '0.7.2', '<'))
{
    $db->exec('PRAGMA foreign_keys = OFF; BEGIN;');

    // Mise à jour base de données
    $db->exec(file_get_contents(ROOT . '/include/data/0.7.2.sql'));

    $db->exec('END;');
}

if (version_compare($v, '0.8.0-beta4', '<'))
{
    // Inscription de l'appid
    $db->exec('PRAGMA application_id = ' . DB::APPID . ';');

    // Changement de la taille de pagesize
    // Cecit devrait améliorer les performances de la DB
    $db->exec('PRAGMA page_size = 4096;');

    // Application du changement de taille de page
    $db->exec('VACUUM;');

    // Désactivation des foreign keys AVANT le début de la transaction
    $db->exec('PRAGMA foreign_keys = OFF;');

    $db->begin();

    $db->import(ROOT . '/include/data/0.8.0.sql');

    $db->commit();

    $config = Config::getInstance();

    // Ajout champ numéro de membre
    $champs = (array) $config->get('champs_membres')->getAll();
    $presets = Membres\Champs::importPresets();

    // Ajout du numéro au début
    $champs = array_merge(['numero' => $presets['numero']], $champs);
    (new Membres\Champs($champs))->save();

    // Si l'ID était l'identificant, utilisons le numéro de membre à la place
    if ($config->get('champ_identifiant') == 'id')
    {
        $config->set('champ_identifiant', 'numero');
        $config->save();
    }

    // Nettoyage de la base de données
    $db->exec('VACUUM;');

    // Mise à jour plan comptable: ajout comptes encaissement
    $comptes = new Compta\Comptes;
    $comptes->importPlan();
}

Utils::clearCaches();

$config->setVersion(garradin_version());

Static_Cache::remove('upgrade');

echo '<h2>Mise à jour terminée.</h2>
<p><a href="'.WWW_URL.'admin/">Retour</a></p>';

if ($redirect)
{
    echo '
    <script type="text/javascript">
    window.setTimeout(function () { 
        window.location.href = "'.WWW_URL.'admin/"; 
        stopAnimatedLoader();
    }, 1000);
    </script>';
}

echo '
</main>
</body>
</html>';
