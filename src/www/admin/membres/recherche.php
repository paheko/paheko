<?php
namespace Garradin;

require_once __DIR__ . '/_inc.php';

$recherche = new Recherche;

$query = (object) [
    'query' => f('q') ? json_decode(f('q'), true) : null,
    'order' => f('order'),
    'limit' => f('limit') ?: 100,
    'desc'  => (bool) f('desc'),
];

$text_query = trim(qg('qt'));
$result = null;
$sql_query = null !== qg('sql') ? 'SELECT * FROM membres LIMIT 10;' : null;
$search = null;
$id = f('id') ?: qg('id');

// Recherche simple
if ($text_query !== '')
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
    $session->requireAccess('membres', Membres::DROIT_ADMIN);
    $sql_query = f('sql_query');
}

// Execute search
if ($query->query || $sql_query) {
    try {
        if ($sql_query) {
            $sql = $sql_query;
        }
        else {
            $sql = $recherche->buildQuery('membres', $query->query, $query->order, $query->desc, $query->limit);
        }

       $result = $recherche->searchSQL('membres', $sql);
    }
    catch (UserException $e) {
        $form->addError($e->getMessage());
    }

    if (f('to_sql')) {
        $sql_query = $sql;
    }
}

if ($result)
{
    if (count($result) == 1 && $text_query !== '') {
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
            $id = $recherche->add($label, $user->id, $type, 'membres', $sql_query ?: $query);
        }

        Utils::redirect('/admin/membres/recherches.php?id=' . $id);
    }

    $tpl->assign('result_header', $membres->getSearchHeaderFields($result));
}
else
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

$columns = $recherche->getColumns('membres');
$is_admin = $session->canAccess('membres', Membres::DROIT_ADMIN);
$schema = $recherche->schema('membres');

$tpl->assign(compact('query', 'sql_query', 'result', 'columns', 'is_admin', 'schema', 'search'));

$tpl->display('admin/membres/recherche.tpl');
