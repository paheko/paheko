<?php
namespace Garradin;

require_once __DIR__ . '/_inc.php';

qv(['id' => 'required|numeric']);

$session->requireAccess('wiki', Membres::DROIT_ADMIN);

$page = $wiki->getByID(qg('id'));

if (!$page)
{
    throw new UserException("Cette page n'existe pas.");
}

if (f('delete'))
{
    if ($form->check('delete_wiki_' . $page->id))
    {
        if ($wiki->delete($page->id))
        {
            Utils::redirect('/admin/wiki/');
        }
        else
        {
            $form->addError('D\'autres pages utilisent cette page comme rubrique parente.');
        }
    }
}

$tpl->assign('page', $page);

$tpl->display('admin/wiki/supprimer.tpl');
