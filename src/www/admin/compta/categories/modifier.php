<?php
namespace Garradin;

require_once __DIR__ . '/../_inc.php';

$session->requireAccess('compta', Membres::DROIT_ADMIN);

$cats = new Compta\Categories;

$id = (int)qg('id');
$cat = $cats->get($id);

if (!$cat)
{
    throw new UserException('Cette catégorie n\'existe pas.');
}

$error = false;

if (!empty($_POST['save']))
{
    if (!Utils::CSRF_check('compta_edit_cat_'.$cat['id']))
    {
        $error = 'Une erreur est survenue, merci de renvoyer le formulaire.';
    }
    else
    {
        try
        {
            $id = $cats->edit($id, [
                'intitule'      =>  Utils::post('intitule'),
                'description'   =>  Utils::post('description'),
            ]);

            if ($cat['type'] == Compta\Categories::DEPENSES)
                $type = 'depenses';
            elseif ($cat['type'] == Compta\Categories::AUTRES)
                $type = 'autres';
            else
                $type = 'recettes';

            Utils::redirect('/admin/compta/categories/?'.$type);
        }
        catch (UserException $e)
        {
            $error = $e->getMessage();
        }
    }
}

$tpl->assign('error', $error);
$tpl->assign('cat', $cat);

$tpl->display('admin/compta/categories/modifier.tpl');
