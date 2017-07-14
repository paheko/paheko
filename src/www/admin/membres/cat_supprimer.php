<?php
namespace Garradin;

require_once __DIR__ . '/_inc.php';

$session->requireAccess('membres', Membres::DROIT_ADMIN);

$cats = new Membres\Categories;

qv(['id' => 'required|numeric']);

$id = (int) qg('id');

$cat = $cats->get($id);

if (!$cat)
{
    throw new UserException("Cette catégorie n'existe pas.");
}

if ($cat->id == $user->id_categorie)
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
