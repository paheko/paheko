<?php
namespace Garradin;

require_once __DIR__ . '/../../../_inc.php';

if ($user['droits']['membres'] < Membres::DROIT_ADMIN)
{
    throw new UserException("Vous n'avez pas le droit d'accéder à cette page.");
}

if (!utils::get('id') || !is_numeric(utils::get('id')))
{
    throw new UserException("Argument du numéro de cotisation manquant.");
}

$cotisations = new Cotisations;

$co = $cotisations->get(utils::get('id'));

if (!$co)
{
    throw new UserException("Cette cotisation n'existe pas.");
}

$error = false;

if (!empty($_POST['delete']))
{
    if (!utils::CSRF_check('delete_co_' . $co['id']))
    {
        $error = 'Une erreur est survenue, merci de renvoyer le formulaire.';
    }
    else
    {
        try {
            $cotisations->delete($co['id']);
            utils::redirect('/admin/membres/cotisations/');
        }
        catch (UserException $e)
        {
            $error = $e->getMessage();
        }
    }
}

$tpl->assign('error', $error);

$tpl->assign('cotisation', $co);

$tpl->display('admin/membres/cotisations/gestion/supprimer.tpl');

?>