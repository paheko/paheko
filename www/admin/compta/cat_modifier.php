<?php

require_once __DIR__ . '/_inc.php';

if ($user['droits']['compta'] < Garradin_Membres::DROIT_ADMIN)
{
    throw new UserException("Vous n'avez pas le droit d'accéder à cette page.");
}

require_once GARRADIN_ROOT . '/include/class.compta_categories.php';

$cats = new Garradin_Compta_Categories;

$id = (int)utils::get('id');
$cat = $cats->get($id);

if (!$cat)
{
    throw new UserException('Cette catégorie n\'existe pas.');
}

$error = false;

if (!empty($_POST['save']))
{
    if (!utils::CSRF_check('compta_edit_cat_'.$cat['id']))
    {
        $error = 'Une erreur est survenue, merci de renvoyer le formulaire.';
    }
    else
    {
        try
        {
            $id = $cats->edit($id,
                array(
                'intitule'      =>  utils::post('intitule'),
                'description'   =>  utils::post('description'),
            ));

            if ($cat['type'] == Garradin_Compta_Categories::DEPENSES)
                $type = 'depenses';
            elseif ($cat['type'] == Garradin_Compta_Categories::AUTRES)
                $type = 'autres';
            else
                $type = 'recettes';

            utils::redirect('/admin/compta/categories.php?'.$type);
        }
        catch (UserException $e)
        {
            $error = $e->getMessage();
        }
    }
}

$tpl->assign('error', $error);
$tpl->assign('cat', $cat);

$tpl->display('admin/compta/cat_modifier.tpl');

?>