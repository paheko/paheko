<?php
namespace Garradin;

require_once __DIR__ . '/_inc.php';

$parent = (int) qg('parent');

if (f('create'))
{
    $form->check('wiki_create', [
        'titre' => 'required',
        'parent'=> 'required|integer'
    ]);

    try {
        $id = $wiki->create([
            'titre'  => f('titre'),
            'parent' => $parent,
        ]);

        Utils::redirect('/admin/wiki/editer.php?id='.$id);
    }
    catch (UserException $e)
    {
        $form->addError($e->getMessage());
    }
}

$tpl->display('admin/wiki/creer.tpl');
