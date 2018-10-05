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
$id = f('id') ?: qg('id');

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
elseif ($id)
{
    $r = $recherche->get($id);

    if (!$r || $r->type != Recherche::TYPE_JSON)
    {
        throw new UserException('Recherche inconnue ou invalide');
    }

    $query = $r->query;
    $order = $r->order;
    $desc = $r->desc;
    $limit = $r->limit;

    $tpl->assign('recherche', $r);
}

if (f('q') !== null)
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

    if (f('save'))
    {
        $query = [
            'query' => $query,
            'order' => $order,
            'limit' => $limit,
            'desc'  => $desc,
        ];

        if ($id)
        {
            $recherche->edit($id, [
                'type'    => Recherche::TYPE_JSON,
                'contenu' => $query,
            ]);
        }
        else
        {
            $id = $recherche->add('Recherche avancée du ' . date('d/m/Y H:i:s'), $user->id, $recherche::TYPE_JSON, 'membres', $query);
        }

        Utils::redirect('/admin/membres/recherches.php?id=' . $id);
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

$tpl->assign('id', $id);
$tpl->assign('query', $query);
$tpl->assign('sql_query', $sql_query);
$tpl->assign('result', $result);
$tpl->assign('order', $order);
$tpl->assign('desc', $desc);
$tpl->assign('limit', $limit);
$tpl->assign('colonnes', $recherche->getColumns('membres'));

$tpl->display('admin/membres/recherche.tpl');
