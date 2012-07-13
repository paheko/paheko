<?php

require_once __DIR__ . '/_inc.php';

if ($user['droits']['compta'] < Garradin_Membres::DROIT_ADMIN)
{
    throw new UserException("Vous n'avez pas le droit d'accéder à cette page.");
}

require_once GARRADIN_ROOT . '/include/class.compta_categories.php';

$cats = new Garradin_Compta_Categories;

$error = false;

if (!empty($_POST['add']))
{
    if (!utils::CSRF_check('compta_ajout_cat'))
    {
        $error = 'Une erreur est survenue, merci de renvoyer le formulaire.';
    }
    else
    {
        try
        {
            $id = $cats->add(array(
                'intitule'      =>  utils::post('intitule'),
                'description'   =>  utils::post('description'),
                'compte'        =>  utils::post('compte'),
                'type'          =>  utils::post('type'),
            ));

            utils::redirect('/admin/compta/categories.php?type='.(int)utils::post('type'));
        }
        catch (UserException $e)
        {
            $error = $e->getMessage();
        }
    }
}

$tpl->assign('error', $error);

$tpl->assign('type', isset($_POST['type']) ? utils::post('type') : Garradin_Compta_Categories::RECETTES);
$tpl->assign('comptes', $comptes->listTree());

$tpl->display('admin/compta/cat_ajouter.tpl');

?>