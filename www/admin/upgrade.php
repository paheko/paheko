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

if (version_compare($config->getVersion(), garradin_version(), '>='))
{
    throw new UserException("Pas de mise à jour à faire.");
}

$db = Garradin_DB::getInstance();

switch ($config->getVersion())
{
    case 0:
        $db->exec('ALTER TABLE membres ADD COLUMN lettre_infos INTEGER DEFAULT 0;');
        $config->setVersion(garradin_version());
        break;
    default:
        throw new UserException("Version inconnue.");
}

utils::redirect('/admin/');

?>