<?php
namespace Garradin;

require_once __DIR__ . '/_inc.php';

$text_query = trim(qg('q') ?? f('q'));

$tpl->assign('list', []);

// Recherche simple
if ($text_query !== '')
{
    $tpl->assign('list', (new Membres)->quickSearch($text_query));
}

$tpl->assign('query', $text_query);

$tpl->display('admin/membres/selector.tpl');
