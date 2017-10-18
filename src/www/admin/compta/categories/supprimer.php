<?php

namespace Garradin;

use Garradin\Compta\Categories;

require_once __DIR__ . '/../_inc.php';

$session->requireAccess('compta', Membres::DROIT_ADMIN);

$cats = new Compta\Categories;

$id = (int)qg('id');
$cat = $cats->get($id);

if (!$cat)
{
    throw new UserException('Cette catégorie n\'existe pas.');
}

if (f('delete') && $form->check('delete_compta_cat_' . $cat->id))
{
    try
    {
        $cats->delete($id);
        Utils::redirect('/admin/compta/categories/');
    }
    catch (UserException $e)
    {
        $form->addError($e->getMessage());
    }
}

$tpl->assign('cat', $cat);

$tpl->display('admin/compta/categories/supprimer.tpl');
