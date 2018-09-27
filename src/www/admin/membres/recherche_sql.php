<?php
namespace Garradin;

require_once __DIR__ . '/_inc.php';

$session->requireAccess('membres', Membres::DROIT_ADMIN);

$recherche = new Recherche;

$query = trim(qg('query'));
$result = null;
$id = (int) qg('id');

if ($id)
{
	$r = $recherche->get($id);

	if (!$r || $r->type != Recherche::TYPE_SQL)
	{
		throw new UserException('Recherche inconnue');
	}

	$query = $r->contenu;
	$tpl->assign('recherche', $r);
}

$tpl->assign('schema', $recherche->schema('membres'));
$tpl->assign('query', $query);

if ($query != '')
{
	try {
		$result = $recherche->searchSQL('membres', $query);
	}
	catch (\Exception $e)
	{
		$form->addError($e->getMessage());
	}

	if (!$form->hasErrors() && qg('save'))
	{
		if ($id)
		{
			$recherche->edit($id, [
				'type'    => Recherche::TYPE_SQL,
				'contenu' => $query,
			]);
		}
		else
		{
			$id = $recherche->add('Recherche SQL du ' . date('d/m/Y H:i:s'), $user->id, $recherche::TYPE_SQL, 'membres', $query);
		}

		Utils::redirect('/admin/membres/recherches.php?id=' . $id);
	}
}

$tpl->assign('result', $result);
$tpl->assign('id', $id);
$tpl->display('admin/membres/recherche_sql.tpl');
