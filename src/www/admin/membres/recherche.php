<?php
namespace Garradin;

require_once __DIR__ . '/_inc.php';

$recherche = new Recherche;

$champs = $config->get('champs_membres');
$text_query = trim(qg('qt'));
$query = null;
$limit = f('limit') ?: 100;
$order = f('order');
$desc = (bool) f('desc');
$sql_query = null;

// Recherche simple
if ($text_query !== '')
{
    $operator = 'LIKE %?%';

    if (is_numeric(trim($text_query)))
    {
        $column = 'numero';
        $operator = '= ?';
    }
    elseif (strpos($text_query, '@') !== false)
    {
        $column = 'email';
    }
    else
    {
        $column = $config->get('champ_identite');
    }

    $query = [[
        'operator' => 'AND',
        'conditions' => [
            [
                'column'   => $column,
                'operator' => $operator,
                'values'   => [$text_query],
            ],
        ],
    ]];
}
elseif (f('q') !== null)
{
    $query = json_decode(f('q'), true);
}

if ($query)
{
    $sql_query = $recherche->buildQuery('membres', $query, $order, $desc, $limit);
    $result = $recherche->searchSQL('membres', $sql_query);

    if (count($result) == 1 && $text_query !== '')
    {
        Utils::redirect(ADMIN_URL . 'membres/fiche.php?id=' . (int)$result[0]->id);
    }

    $tpl->assign('result_header', $membres->getSearchHeaderFields($result));
}
else
{
    $query = [[
        'operator' => 'AND',
        'conditions' => [
            [
                'column'   => $config->get('champ_identite'),
                'operator' => '= ?',
                'values'   => ['Souad Massi'],
            ],
        ],
    ]];
    $result = null;
}

$tpl->assign('query', $query);
$tpl->assign('sql_query', $sql_query);
$tpl->assign('result', $result);
$tpl->assign('order', $order);
$tpl->assign('desc', $desc);
$tpl->assign('limit', $limit);
$tpl->assign('colonnes', $recherche->getColumns('membres'));

$tpl->display('admin/membres/recherche.tpl');
