<?php
namespace Garradin;

require_once __DIR__ . '/_inc.php';

$session->requireAccess('wiki', Membres::DROIT_ECRITURE);

qv(['id' => 'required|numeric']);

$page = $wiki->getById(qg('id'));
$date = false;

if (!$page)
{
    throw new UserException('Page introuvable.');
}

if (!empty($page->contenu))
{
    $page->chiffrement = $page->contenu->chiffrement;
    $page->contenu = $page->contenu->contenu;
}

if (f('date'))
{
    $date = f('date') . ' ' . sprintf('%02d:%02d', f('date_h'), f('date_min'));
}

if (f('save'))
{
    $form->check('wiki_edit_' . $page->id, [
        'titre'          => 'required',
        'uri'            => 'required',
        'parent'         => 'numeric',
        'droit_lecture'  => 'numeric',
        'droit_ecriture' => 'numeric',
    ]);
    
    if ($page->date_modification > (int) f('debut_edition'))
    {
        $form->addError('La page a été modifiée par quelqu\'un d\'autre depuis que vous avez commencé l\'édition.');
    }

    if (!$form->hasErrors())
    {
        try {
            $wiki->edit($page->id, [
                'titre'         =>  f('titre'),
                'uri'           =>  f('uri'),
                'parent'        =>  f('parent'),
                'droit_lecture' =>  f('droit_lecture'),
                'droit_ecriture'=>  f('droit_ecriture'),
                'date_creation' =>  $date,
            ]);

            $wiki->editRevision($page->id, (int) f('revision_edition'), [
                'contenu'      =>  f('contenu'),
                'modification' =>  f('modification'),
                'id_auteur'    =>  $user->id,
                'chiffrement'  =>  f('chiffrement'),
            ]);

            $page = $wiki->getById($page->id);

            Utils::redirect('/admin/wiki/?'.$page->uri);
        }
        catch (UserException $e)
        {
            $form->addError($e->getMessage());
        }
    }
}

$parent = (int) f('parent') ?: (int) $page->parent;
$tpl->assign('parent', $parent ? $wiki->getTitle($parent) : 0);

$tpl->assign('page', $page);

$tpl->assign('wiki', $wiki);

$tpl->assign('time', time());
$tpl->assign('date', $date ? strtotime($date) : $page->date_creation);

$tpl->assign('custom_js', ['wiki_editor.js', 'wiki-encryption.js']);

$tpl->display('admin/wiki/editer.tpl');
