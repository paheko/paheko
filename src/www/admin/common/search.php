<?php
namespace Garradin;

use Garradin\Accounting\Years;

require_once __DIR__ . '/../_inc.php';

if (empty($target) || !in_array($target, Recherche::TARGETS)) {
	throw new UserException('Cible inconnue');
}

$recherche = new Recherche;

$query = (object) [
	'query' => f('q') ? json_decode(f('q'), true) : null,
	'order' => f('order'),
	'limit' => f('limit') ?: 100,
	'desc'  => (bool) f('desc'),
];

$text_query = trim(qg('qt'));
$result = null;
$sql_query = null;
$search = null;
$id = f('id') ?: qg('id');

// Recherche simple
if ($text_query !== '' && $target === 'membres')
{
	$query = $recherche->buildSimpleMemberQuery($text_query);
}
// Recherche existante
elseif ($id)
{
	$search = $recherche->get($id);

	if (!$search) {
		throw new UserException('Recherche inconnue ou invalide');
	}

	if ($search->type === Recherche::TYPE_SQL) {
		$sql_query = $search->contenu;
	}
	else {
		$query = $search->query;
		$query->limit = (int) f('limit') ?: $query->limit;
	}
}

// Recherche SQL
if (f('sql_query')) {
	// Only admins can run custom queries, others can only run saved queries
	$session->requireAccess($target, Membres::DROIT_ADMIN);
	$sql_query = f('sql_query');
}

// Execute search
if ($query->query || $sql_query) {
	try {
		if ($sql_query) {
			$sql = $sql_query;
		}
		else {
			$sql = $recherche->buildQuery($target, $query->query, $query->order, $query->desc, $query->limit);
		}

	   $result = $recherche->searchSQL($target, $sql);
	}
	catch (UserException $e) {
		$form->addError($e->getMessage());
	}

	if (f('to_sql')) {
		$sql_query = $sql;
	}
}

if (null !== $result)
{
	if (count($result) == 1 && $text_query !== '' && $target === 'membres') {
		Utils::redirect(ADMIN_URL . 'membres/fiche.php?id=' . (int)$result[0]->id);
	}

	if (f('save') && !$form->hasErrors())
	{
		$type = $sql_query ? Recherche::TYPE_SQL : Recherche::TYPE_JSON;

		if ($id) {
			$recherche->edit($id, [
				'type'    => $type,
				'contenu' => $sql_query ?: $query,
			]);
		}
		else
		{
			$label = $sql_query ? 'Recherche SQL du ' : 'Recherche avancée du ';
			$label .= date('d/m/Y à H:i:s');
			$id = $recherche->add($label, $user->id, $type, $target, $sql_query ?: $query);
		}

		$url = $target == 'compta' ? '/admin/acc/saved_searches.php?id=' : '/admin/membres/recherches.php?id=';
		Utils::redirect($url . $id);
	}

	$tpl->assign('result_header', $recherche->getResultHeader($target, $result));
}
elseif ($target === 'membres')
{
	$query->query = [[
		'operator' => 'AND',
		'conditions' => [
			[
				'column'   => $config->get('champ_identite'),
				'operator' => '= ?',
				'values'   => [''],
			],
		],
	]];
	$result = null;
}
elseif ($target === 'compta')
{
	$years = Years::list();
	$query->query = [[
		'operator' => 'AND',
		'conditions' => [
			[
				'column'   => 't.id_year',
				'operator' => '= ?',
				'values'   => [qg('year')],
			],
			[
				'column'   => 't.reference',
				'operator' => 'LIKE %?%',
				'values'   => '',
			],
		],
	]];
	$query->desc = true;
	$result = null;
}

$columns = $recherche->getColumns($target);
$is_admin = $session->canAccess($target, Membres::DROIT_ADMIN);
$schema = $recherche->schema($target);

$tpl->assign(compact('query', 'sql_query', 'result', 'columns', 'is_admin', 'schema', 'search'));

if ($target == 'compta') {
	$tpl->display('acc/search.tpl');
}
else {
	$tpl->display('admin/membres/recherche.tpl');
}
