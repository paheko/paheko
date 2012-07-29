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

if (!empty($_POST['delete']))
{
    if (!utils::CSRF_check('delete_compta_cat_'.$cat['id']))
    {
        $error = 'Une erreur est survenue, merci de renvoyer le formulaire.';
    }
    else
    {
        try
        {
            $cats->delete($id);
            utils::redirect('/admin/compta/categories.php');
        }
        catch (UserException $e)
        {
            $error = $e->getMessage();
        }
    }
}

$tpl->assign('error', $error);
$tpl->assign('cat', $cat);

$tpl->display('admin/compta/cat_supprimer.tpl');

?>