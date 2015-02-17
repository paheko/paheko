<?php

namespace Garradin;
require_once __DIR__ . '/_inc.php';

if (!empty($_SERVER['QUERY_STRING']))
{
    $page_uri = Wiki::transformTitleToURI(rawurldecode($_SERVER['QUERY_STRING']));
    $page = $wiki->getByURI($page_uri);
}
else
{
    $page = $wiki->getByURI($config->get('accueil_wiki'));
}

if (!$page)
{
    $tpl->assign('uri', $page_uri);
    $tpl->assign('can_edit', $wiki->canWritePage(Wiki::ECRITURE_NORMAL));
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