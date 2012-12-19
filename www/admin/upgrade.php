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

utils::clearCaches();

$config->setVersion(garradin_version());

echo '<h4>Mise à jour terminée.</h4>
<p><a href="./">Retour</a></p>';

?>