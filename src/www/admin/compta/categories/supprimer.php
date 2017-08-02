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

if (!empty($_POST['delete']))
{
    if (!Utils::CSRF_check('delete_compta_cat_'.$cat['id']))
    {
        $error = 'Une erreur est survenue, merci de renvoyer le formulaire.';
    }
    else
    {
        try
        {
            $cats->delete($id);
            Utils::redirect('/admin/compta/categories/');
        }
        catch (UserException $e)
        {
            $error = $e->getMessage();
        }
    }
}

$tpl->assign('error', $error);
$tpl->assign('cat', $cat);

$tpl->display('admin/compta/categories/supprimer.tpl');
