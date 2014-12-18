<?php
namespace Garradin;

require_once __DIR__ . '/_inc.php';

if (!trim(Utils::get('id')))
{
    throw new UserException("Page inconnue.");
}

$page = $wiki->getByID(Utils::get('id'));

if (!$page)
{
    throw new UserException("Cette page n'existe pas.");
}

if (!$wiki->canReadPage($page['droit_lecture']))
{
    throw new UserException("Vous n'avez pas le droit de voir cette page.");
}

if (Utils::get('diff'))
{
    $revs = explode('.', Utils::get('diff'));

    if (count($revs) != 2)
    {
        throw new UserException("Erreur de paramètre.");
    }

    $rev1 = $wiki->getRevision($page['id'], (int)$revs[0]);
    $rev2 = $wiki->getRevision($page['id'], (int)$revs[1]);

    if ($rev1['chiffrement'])
    {
        $rev1['contenu'] = 'Contenu chiffré';
    }

    if ($rev2['chiffrement'])
    {
        $rev2['contenu'] = 'Contenu chiffré';
    }

    $tpl->assign('rev1', $rev1);
    $tpl->assign('rev2', $rev2);
    $tpl->assign('diff', true);
}
else
{
    $tpl->assign('revisions', $wiki->listRevisions($page['id']));
}

$tpl->assign('can_edit', $wiki->canWritePage($page['droit_ecriture']));
$tpl->assign('page', $page);

$tpl->display('admin/wiki/historique.tpl');

?>