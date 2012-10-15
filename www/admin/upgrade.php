<?php

define('GARRADIN_UPGRADE_PROCESS', true);

require_once __DIR__ . '/../../include/init.php';

if (!file_exists(GARRADIN_DB_FILE))
{
    utils::redirect('/admin/install.php');
}

require_once GARRADIN_ROOT . '/include/class.db.php';
require_once GARRADIN_ROOT . '/include/class.config.php';
$config = Garradin_Config::getInstance();

$v = $config->getVersion();

if (version_compare($v, garradin_version(), '>='))
{
    throw new UserException("Pas de mise à jour à faire.");
}

$db = Garradin_DB::getInstance();

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
    require GARRADIN_ROOT . '/include/class.compta_comptes.php';

    $comptes = new Garradin_Compta_Comptes;
    $comptes->importPlan();

    require GARRADIN_ROOT . '/include/class.compta_categories.php';

    $comptes = new Garradin_Compta_Categories;
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