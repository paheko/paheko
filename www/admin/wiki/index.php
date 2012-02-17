<?php

require_once __DIR__ . '/_inc.php';

if (!empty($_SERVER['QUERY_STRING']))
{
    $page = $wiki->getByURI($_SERVER['QUERY_STRING']);
}
else
{
    $page = $wiki->getByURI($config->get('accueil_wiki'));
}

$tpl->assign('page', $page);
$tpl->assign('can_edit', $wiki->canWritePage($page['droit_ecriture']));

$tpl->display('admin/wiki/page.tpl');

?>