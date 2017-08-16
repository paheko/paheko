<?php

namespace Garradin;

use Garradin\Compta\Categories;

require_once __DIR__ . '/../_inc.php';

$session->requireAccess('compta', Membres::DROIT_ADMIN);

$cats = new Categories;

if (f('add'))
{
    $form->check('compta_ajout_cat', [
        'intitule' => 'required|string',
        'compte'   => 'required|in_table:compta_comptes,id',
        'type'     => 'required|in:' . implode(',', [Categories::DEPENSES, Categories::RECETTES, Categories::AUTRES]),
    ]);

    if (!$form->hasErrors())
    {
        try
        {
            $id = $cats->add([
                'intitule'      =>  f('intitule'),
                'description'   =>  f('description'),
                'compte'        =>  f('compte'),
                'type'          =>  f('type'),
            ]);

            if (f('type') == Categories::DEPENSES)
                $type = 'depenses';
            elseif (f('type') == Categories::AUTRES)
                $type = 'autres';
            else
                $type = 'recettes';

            Utils::redirect('/admin/compta/categories/?'.$type);
        }
        catch (UserException $e)
        {
            $form->addError($e->getMessage());
        }
    }
}

$tpl->assign('type', f('type') !== null ? f('type') : Categories::RECETTES);
$tpl->assign('comptes', $comptes->listTree());
$tpl->assign('categories', $cats);

$tpl->display('admin/compta/categories/ajouter.tpl');
