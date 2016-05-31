<?php
namespace Garradin;

require_once __DIR__ . '/../_inc.php';

if ($user['droits']['membres'] < Membres::DROIT_ADMIN)
{
    throw new UserException("Vous n'avez pas le droit d'accéder à cette page.");
}

$cats = new Membres\Categories;

if (empty($_GET['id']) || !is_numeric($_GET['id']))
{
    throw new UserException("Argument du numéro de catégorie manquant.");
}

$id = (int) $_GET['id'];

$cat = $cats->get($id);

if (!$cat)
{
    throw new UserException("Cette catégorie n'existe pas.");
}

if ($cat['id'] == $user['id_categorie'])
{
    throw new UserException("Vous ne pouvez pas supprimer votre catégorie.");
}

$error = false;

if (!empty($_POST['delete']))
{
    if (!Utils::CSRF_check('delete_cat_'.$id))
    {
        $error = 'Une erreur est survenue, merci de renvoyer le formulaire.';
    }
    else
    {
        try {
            $cats->remove($id);
            Utils::redirect('/admin/membres/categories.php');
        }
        catch (UserException $e)
        {
            $error = $e->getMessage();
        }
    }
}

$tpl->assign('cat', $cat);
$tpl->assign('error', $error);

$tpl->display('admin/membres/cat_supprimer.tpl');

?>