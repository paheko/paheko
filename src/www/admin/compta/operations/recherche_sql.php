<?php
namespace Garradin;

require_once __DIR__ . '/../_inc.php';

if ($user['droits']['compta'] < Membres::DROIT_ADMIN)
{
    throw new UserException("Vous n'avez pas le droit d'accéder à cette page.");
}

$journal = new Compta\Journal;

$query = trim(Utils::get('query'));

$tpl->assign('schema', $journal->schemaSQL());
$tpl->assign('query', $query);

if ($query != '')
{
    try {
        $tpl->assign('result', $journal->searchSQL($query));
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

$tpl->display('admin/compta/operations/recherche_sql.tpl');
