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

if (!$page)
{
    $tpl->assign('uri', $_SERVER['QUERY_STRING']);
    $tpl->assign('can_edit', $wiki->canWritePage(Garradin_Wiki::ECRITURE_NORMAL));
    $tpl->assign('can_read', true);
}
else
{
    $tpl->assign('can_read', $wiki->canReadPage($page['droit_lecture']));
    $tpl->assign('can_edit', $wiki->canWritePage($page['droit_ecriture']));
    $tpl->assign('children', $wiki->getList($page['uri'] == $config->get('accueil_wiki') ? 0 : $page['id']));
    $tpl->assign('breadcrumbs', $wiki->listBackBreadCrumbs($page['id']));
    $tpl->assign('auteur', $membres->getNom($page['contenu']['id_auteur']));
}

$tpl->assign('page', $page);

$tpl->display('admin/wiki/page.tpl');

?>