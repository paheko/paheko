<?php
namespace Garradin;

require_once __DIR__ . '/../_inc.php';

$session->requireAccess('membres', Membres::DROIT_ADMIN);

$cats = new Membres\Categories;

if (f('save'))
{
    $form->check('new_cat', [
        'nom' => 'required',
    ]);

    if (!$form->hasErrors())
    {
        $cats->add([
            'nom' => f('nom'),
        ]);

        Utils::redirect('/admin/membres/categories/');
    }
}

$tpl->assign('liste', $cats->listCompleteWithStats());

$tpl->display('admin/membres/categories/index.tpl');
