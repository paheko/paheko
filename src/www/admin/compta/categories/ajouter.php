<?php
namespace Garradin;

require_once __DIR__ . '/../_inc.php';

if ($user['droits']['compta'] < Membres::DROIT_ADMIN)
{
    throw new UserException("Vous n'avez pas le droit d'accéder à cette page.");
}

$cats = new Compta\Categories;

$error = false;

if (!empty($_POST['add']))
{
    if (!Utils::CSRF_check('compta_ajout_cat'))
    {
        $error = 'Une erreur est survenue, merci de renvoyer le formulaire.';
    }
    else
    {
        try
        {
            $id = $cats->add([
                'intitule'      =>  Utils::post('intitule'),
                'description'   =>  Utils::post('description'),
                'compte'        =>  Utils::post('compte'),
                'type'          =>  Utils::post('type'),
            ]);

            if (Utils::post('type') == Compta\Categories::DEPENSES)
                $type = 'depenses';
            elseif (Utils::post('type') == Compta\Categories::AUTRES)
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

$tpl->assign('type', isset($_POST['type']) ? Utils::post('type') : Compta\Categories::RECETTES);
$tpl->assign('comptes', $comptes->listTree());

$tpl->display('admin/compta/categories/ajouter.tpl');

?>