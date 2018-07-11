<?php
namespace Garradin;

require_once __DIR__ . '/../_inc.php';

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

        Utils::redirect(ADMIN_URL . 'config/categories/');
    }
}

$tpl->assign('liste', $cats->listCompleteWithStats());

$tpl->display('admin/config/categories/index.tpl');
