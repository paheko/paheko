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
    $page_uri = '';
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
    $tpl->assign('children', $wiki->getList($page_uri == '' ? 0 : $page['id'], true));
    $tpl->assign('breadcrumbs', $wiki->listBackBreadCrumbs($page['id']));
    $tpl->assign('auteur', $membres->getNom($page['contenu']['id_auteur']));

    $images = Fichiers::listLinkedFiles(Fichiers::LIEN_WIKI, $page['id'], true);

    if ($images && !$page['contenu']['chiffrement'])
    {
        $images = Fichiers::filterFilesUsedInText($images, $page['contenu']['contenu']);
    }

    $fichiers = Fichiers::listLinkedFiles(Fichiers::LIEN_WIKI, $page['id'], false);

    if ($fichiers && !$page['contenu']['chiffrement'])
    {
        $fichiers = Fichiers::filterFilesUsedInText($fichiers, $page['contenu']['contenu']);
    }

    $tpl->assign('images', $images);
    $tpl->assign('fichiers', $fichiers);
}

$tpl->assign('page', $page);

$tpl->assign('custom_js', ['wiki_gallery.js']);

$tpl->display('admin/wiki/page.tpl');