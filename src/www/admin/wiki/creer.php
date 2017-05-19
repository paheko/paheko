<?php
namespace Garradin;

require_once __DIR__ . '/_inc.php';

$parent = (int) Utils::get('parent') ?: 0;

if (f('create'))
{
    fc('wiki_create', [
        'titre' => 'required',
        'parent'=> 'required|integer'
    ], $form_errors);

    try {
        $id = $wiki->create([
            'titre'  => f('titre'),
            'parent' => $parent,
        ]);

        Utils::redirect('/admin/wiki/editer.php?id='.$id);
    }
    catch (UserException $e)
    {
        $form_errors[] = $e->getMessage();
    }
}

$tpl->display('admin/wiki/creer.tpl');
