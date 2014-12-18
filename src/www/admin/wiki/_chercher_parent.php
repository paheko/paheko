<?php
namespace Garradin;

require_once __DIR__ . '/_inc.php';

if ((trim(Utils::get('parent')) == '') || !is_numeric(Utils::get('parent')))
{
    throw new UserException('Numéro de page parent invalide.');
}

$parent = (int) Utils::get('parent');

$tpl->assign('parent', $parent);
$tpl->assign('list', $wiki->listBackParentTree($parent));

function tpl_display_tree($params)
{
    if (isset($params['tree']))
        $tree = $params['tree'];
    else
        $tree = $params;

    $out = '<ul>';

    foreach ($tree as $node)
    {
        $out .= '<li'.(Utils::get('parent') == $node['id'] ? ' class="current"' : '').'><h3><a href="?parent='.(int)$node['id'].'">'.htmlspecialchars($node['titre'], ENT_QUOTES, 'UTF-8', false).'</a></h3>';

        if (!empty($node['children']))
        {
            $out .= tpl_display_tree($node['children']);
        }

        $out .= '</li>';
    }

    $out .= '</ul>';

    return $out;
}

$tpl->register_function('display_tree', 'Garradin\tpl_display_tree');

$tpl->display('admin/wiki/_chercher_parent.tpl');

?>