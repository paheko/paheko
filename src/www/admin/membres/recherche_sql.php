<?php
namespace Garradin;

require_once __DIR__ . '/_inc.php';

$session->requireAccess('membres', Membres::DROIT_ADMIN);

$recherche = new Recherche;

$query = trim(qg('query'));
$error = null;
$result = null;

$tpl->assign('schema', $recherche->schema('membres'));
$tpl->assign('query', $query);

if ($query != '')
{
	try {
		$result = $recherche->searchSQL('membres', $query);
	}
	catch (\Exception $e)
	{
		$error = $e->getMessage();
	}

	if (!$error && qg('save'))
	{
		$id = $recherche->add('Recherche SQL du ' . date('d/m/Y H:i:s'), $user->id, $recherche::TYPE_SQL, 'membres', $query);
		Utils::redirect('/admin/recherches.php?id=' . $id);
	}
}

$tpl->assign('result', $result);
$tpl->display('admin/membres/recherche_sql.tpl');
