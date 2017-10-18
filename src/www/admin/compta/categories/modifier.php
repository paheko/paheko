<?php

namespace Garradin;

use Garradin\Compta\Categories;

require_once __DIR__ . '/../_inc.php';

$session->requireAccess('compta', Membres::DROIT_ADMIN);

$cats = new Categories;

$id = (int)qg('id');
$cat = $cats->get($id);

if (!$cat)
{
    throw new UserException('Cette catégorie n\'existe pas.');
}

if (f('save'))
{
    $form->check('compta_edit_cat_' . $cat->id, [
        'intitule' => 'required|string',
    ]);

    if (!$form->hasErrors())
    {
        try
        {
            $id = $cats->edit($id, [
                'intitule'      =>  f('intitule'),
                'description'   =>  f('description'),
            ]);

            if ($cat->type == Compta\Categories::DEPENSES)
                $type = 'depenses';
            elseif ($cat->type == Compta\Categories::AUTRES)
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

$tpl->assign('cat', $cat);

$tpl->display('admin/compta/categories/modifier.tpl');
