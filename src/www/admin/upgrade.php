<?php

namespace Garradin;

use Garradin\Membres\Session;

const UPGRADE_PROCESS = true;

require_once __DIR__ . '/../../include/test_required.php';
require_once __DIR__ . '/../../include/init.php';

$config = Config::getInstance();

$v = $config->getVersion();

if (version_compare($v, garradin_version(), '>='))
{
    throw new UserException("Pas de mise à jour à faire.");
}

// versions pré-0.7.0: démerdez-vous !
if (!$v || version_compare($v, '0.7.0', '<'))
{
    throw new UserException("Votre version de Garradin est trop ancienne pour être mise à jour. Mettez à jour vers Garradin 0.8.5 avant de faire la mise à jour vers cette version.");
}

Install::checkAndCreateDirectories();

if (Static_Cache::exists('upgrade'))
{
    $path = Static_Cache::getPath('upgrade');
    throw new UserException('Une mise à jour est déjà en cours.'
        . PHP_EOL . 'Si celle-ci a échouée et que vous voulez ré-essayer, supprimez le fichier suivant:'
        . PHP_EOL . $path);
}

// Voir si l'utilisateur est loggé, on le fait ici pour le cas où
// il y aurait déjà eu des entêtes envoyés au navigateur plus bas
$session = new Session;
$user_is_logged = $session->isLogged(true);

Static_Cache::store('upgrade', 'Mise à jour en cours.');

$db = DB::getInstance();
$redirect = true;

// Créer une sauvegarde automatique
$backup_name = (new Sauvegarde)->create('pre-upgrade-' . garradin_version());

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

try {
    if (version_compare($v, '1.0.0-alpha1', '<'))
    {
        $db->beginSchemaUpdate();
        $db->import(ROOT . '/include/data/1.0.0_migration.sql');
        $db->commitSchemaUpdate();

        // Import nouveau plan comptable
        $chart = new \Garradin\Entities\Accounting\Chart;
        $chart->label = 'Plan comptable associatif 2018';
        $chart->country = 'FR';
        $chart->code = 'PCA2018';
        $chart->save();
        $chart->accounts()->importCSV(ROOT . '/include/data/charts/fr_2018.csv');
    }

    // Vérification de la cohérence des clés étrangères
    $db->foreignKeyCheck();

    Utils::clearCaches();

    $config->setVersion(garradin_version());

    Static_Cache::remove('upgrade');

    // Réinstaller les plugins système si nécessaire
    Plugin::checkAndInstallSystemPlugins();

    // Mettre à jour les plugins si nécessaire
    foreach (Plugin::listInstalled() as $id=>$infos)
    {
        // Ne pas tenir compte des plugins dont le code n'est pas dispo
        if ($infos->disabled)
        {
            continue;
        }

        $plugin = new Plugin($id);

        if ($plugin->needUpgrade())
        {
            $plugin->upgrade();
        }

        unset($plugin);
    }
}
catch (\Exception $e)
{
    $s = new Sauvegarde;
    $s->restoreFromLocal($backup_name);
    $s->remove($backup_name);
    Static_Cache::remove('upgrade');
    throw $e;
}

// Forcer à rafraîchir les données de la session si elle existe
if ($user_is_logged)
{
    $session->refresh();
}

echo '<h2>Mise à jour terminée.</h2>
<p><a href="'.ADMIN_URL.'">Retour</a></p>';

if ($redirect)
{
    echo '
    <script type="text/javascript">
    window.setTimeout(function () { 
        window.location.href = "'.ADMIN_URL.'"; 
        stopAnimatedLoader();
    }, 1000);
    </script>';
}

echo '
</main>
</body>
</html>';
