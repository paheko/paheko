<?php

require_once __DIR__ . '/../_inc.php';

if ($user['droits']['membres'] < Garradin_Membres::DROIT_ADMIN)
{
    throw new UserException("Vous n'avez pas le droit d'accéder à cette page.");
}

require_once GARRADIN_ROOT . '/include/class.membres_categories.php';
$cats = new Garradin_Membres_Categories;

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

$error = false;

if (!empty($_POST['save']))
{
    if (!utils::CSRF_check('edit_cat_'.$id))
    {
        $error = 'Une erreur est survenue, merci de renvoyer le formulaire.';
    }
    else
    {
        try {
            $cats->edit($id, array(
                'nom'           =>  utils::post('nom'),
                'description'   =>  utils::post('description'),
                'montant_cotisation' => (float) utils::post('montant_cotisation'),
                'duree_cotisation' => (int) utils::post('duree_cotisation'),
                'droit_wiki'    =>  (int) utils::post('droit_wiki'),
                'droit_compta'  =>  (int) utils::post('droit_compta'),
                'droit_config'  =>  (int) utils::post('droit_config'),
                'droit_membres' =>  (int) utils::post('droit_membres'),
                'droit_connexion' => (int) utils::post('droit_connexion'),
                'droit_inscription' => (int) utils::post('droit_inscription'),
                'cacher'        =>  (int) utils::post('cacher'),
            ));

            utils::redirect('/admin/membres/categories.php');
        }
        catch (UserException $e)
        {
            $error = $e->getMessage();
        }
    }
}

$tpl->assign('cat', $cat);
$tpl->assign('error', $error);

$tpl->display('admin/membres/cat_modifier.tpl');

?>