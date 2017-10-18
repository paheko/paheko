<?php
namespace Garradin;

require_once __DIR__ . '/_inc.php';

$session->requireAccess('membres', Membres::DROIT_ADMIN);

$query = trim(qg('query'));

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
