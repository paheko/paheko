<?php

require_once __DIR__ . '/_inc.php';

$q = trim(utils::get('q'));

$tpl->assign('recherche', $q);

if (utils::get('q'))
{
    $r = $wiki->search($q);
    $tpl->assign('resultats', $r);
    $tpl->assign('nb_resultats', count($r));
}

function tpl_clean_snippet($str)
{
    return preg_replace('!&lt;(/?b)&gt;!', '<$1>', $str);
}

$tpl->register_modifier('clean_snippet', 'tpl_clean_snippet');

$tpl->display('admin/wiki/chercher.tpl');

?>