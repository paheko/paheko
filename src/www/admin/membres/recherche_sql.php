<?php
namespace Garradin;

require_once __DIR__ . '/../_inc.php';

if ($user['droits']['membres'] < Membres::DROIT_ADMIN)
{
    throw new UserException("Vous n'avez pas le droit d'accéder à cette page.");
}

$query = trim(Utils::get('query'));

$tpl->assign('schema', $membres->schemaSQL());
$tpl->assign('query', $query);

if ($query != '')
{
    try {
        $tpl->assign('result', $membres->searchSQL($query));
    }
    catch (\Exception $e)
    {
        $tpl->assign('result', null);
        $tpl->assign('error', $e->getMessage());
    }
}
else
{
    $tpl->assign('result', null);
}

$tpl->display('admin/membres/recherche_sql.tpl');

?>