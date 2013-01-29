<?php
namespace Garradin;

require_once __DIR__ . '/../_inc.php';

if ($user['droits']['membres'] < Membres::DROIT_ADMIN)
{
    throw new UserException("Vous n'avez pas le droit d'accéder à cette page.");
}

header('Content-type: application/csv');
header('Content-Disposition: attachment; filename="Export membres - ' . $config->get('nom_asso') . ' - ' . date('Y-m-d') . '.csv"');
$membres->toCSV();
exit;

?>