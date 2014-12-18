<?php
namespace Garradin;

require_once __DIR__ . '/_inc.php';

if ($user['droits']['wiki'] < Membres::DROIT_ADMIN)
{
    throw new UserException("Vous n'avez pas le droit d'accéder à cette page.");
}

if (!trim(Utils::get('id')))
{
    throw new UserException("Page inconnue.");
}

$page = $wiki->getByID(Utils::get('id'));

if (!$page)
{
    throw new UserException("Cette page n'existe pas.");
}


$error = false;

if (!empty($_POST['delete']))
{
    if (!Utils::CSRF_check('delete_wiki_'.$page['id']))
    {
        $error = 'Une erreur est survenue, merci de renvoyer le formulaire.';
    }
    else
    {
        if ($wiki->delete($page['id']))
        {
            Utils::redirect('/admin/wiki/');
        }
        else
        {
            $error = "D'autres pages utilisent cette page comme rubrique parente.";
        }
    }
}

$tpl->assign('error', $error);
$tpl->assign('page', $page);

$tpl->display('admin/wiki/supprimer.tpl');

?>