<?php
namespace Garradin;

require_once __DIR__ . '/../_inc.php';

if ($user['droits']['membres'] < Membres::DROIT_ADMIN)
{
    throw new UserException("Vous n'avez pas le droit d'accéder à cette page.");
}

if (empty($_POST['selected']))
{
    throw new UserException("Aucun membre sélectionné.");
}

foreach ($_POST['selected'] as &$id)
{
    $id = (int) $id;

    // On ne permet pas d'action collective sur l'utilisateur courant pour éviter les risques
    // d'erreur genre "oh je me suis supprimé du coup j'ai plus accès à rien"
    if ($id == $user['id'])
    {
        throw new UserException("Il n'est pas possible de se modifier ou supprimer soi-même.");
    }
}

$error = false;

if (!empty($_POST['move_ok']))
{
    if (!Utils::CSRF_check('membres_action'))
    {
        $error = 'Une erreur est survenue, merci de renvoyer le formulaire.';
        $_POST['move'] = true;
    }
    else
    {
        if (!empty($_POST['id_categorie']))
        {
            $membres->changeCategorie($_POST['id_categorie'], $_POST['selected']);
        }

        Utils::redirect('/admin/membres/');
    }
}
elseif (!empty($_POST['delete_ok']))
{
    if (!Utils::CSRF_check('membres_action'))
    {
        $error = 'Une erreur est survenue, merci de renvoyer le formulaire.';
        $_POST['delete'] = true;
    }
    else
    {
        $membres->delete($_POST['selected']);

        Utils::redirect('/admin/membres/');
    }
}

$tpl->assign('selected', $_POST['selected']);
$tpl->assign('nb_selected', count($_POST['selected']));

if (!empty($_POST['move']))
{
    $cats = new Membres\Categories;

    $tpl->assign('membres_cats', $cats->listSimple());
    $tpl->assign('action', 'move');
}
elseif (!empty($_POST['delete']))
{
    $tpl->assign('action', 'delete');
}
else
{
    throw new UserException('Aucune action sélectionée.');
}

$tpl->assign('error', $error);

$tpl->display('admin/membres/action.tpl');
