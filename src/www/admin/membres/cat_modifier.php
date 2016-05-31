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

$error = false;

if (!empty($_POST['save']))
{
    if (!Utils::CSRF_check('edit_cat_'.$id))
    {
        $error = 'Une erreur est survenue, merci de renvoyer le formulaire.';
    }
    else
    {
        $data = [
                'nom'           =>  Utils::post('nom'),
                'description'   =>  Utils::post('description'),
                'droit_wiki'    =>  (int) Utils::post('droit_wiki'),
                'droit_compta'  =>  (int) Utils::post('droit_compta'),
                'droit_config'  =>  (int) Utils::post('droit_config'),
                'droit_membres' =>  (int) Utils::post('droit_membres'),
                'droit_connexion' => (int) Utils::post('droit_connexion'),
                'droit_inscription' => (int) Utils::post('droit_inscription'),
                'cacher'        =>  (int) Utils::post('cacher'),
                'id_cotisation_obligatoire' => (int) Utils::post('id_cotisation_obligatoire'),
        ];

        // Ne pas permettre de modifier la connexion, l'accès à la config et à la gestion des membres
        // pour la catégorie du membre qui édite les catégories, sinon il pourrait s'empêcher
        // de se connecter ou n'avoir aucune catégorie avec le droit de modifier les catégories !
        if ($cat['id'] == $user['id_categorie'])
        {
            $data['droit_connexion'] = Membres::DROIT_ACCES;
            $data['droit_config'] = Membres::DROIT_ADMIN;
            $data['droit_membres'] = Membres::DROIT_ADMIN;
        }

        try {
            $cats->edit($id, $data);

            if ($id == $user['id_categorie'])
            {
                $membres->updateSessionData();
            }

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

$tpl->assign('readonly', $cat['id'] == $user['id_categorie'] ? 'disabled="disabled"' : '');

$cotisations = new Cotisations;
$tpl->assign('cotisations', $cotisations->listCurrent());

$tpl->display('admin/membres/cat_modifier.tpl');
