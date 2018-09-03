<?php
namespace Garradin;

require_once __DIR__ . '/_inc.php';

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
    $sql_query = $membres->buildSQLSearchQuery($query, $order, $desc, $limit);
    $result = $membres->searchSQL($sql_query);

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

$colonnes = [];

foreach ($champs->getList() as $champ => $config)
{
    $colonne = [
        'label' => $config->title,
        'type'  => 'text',
        'null'  => true,
    ];

    if ($config->type == 'checkbox')
    {
        $colonne['type'] = 'boolean';
    }
    elseif ($config->type == 'select')
    {
        $colonne['type'] = 'enum';
        $colonne['values'] = $config->options;
    }
    elseif ($config->type == 'multiple')
    {
        $colonne['type'] = 'bitwise';
        $colonne['values'] = $config->options;
    }
    elseif ($config->type == 'date' || $config->type == 'datetime')
    {
        $colonne['type'] = $config->type;
    }
    elseif ($config->type == 'number' || $champ == 'numero')
    {
        $colonne['type'] = 'integer';
    }

    $colonnes[$champ] = $colonne;
}

$tpl->assign('colonnes', $colonnes);

$tpl->display('admin/membres/recherche.tpl');
