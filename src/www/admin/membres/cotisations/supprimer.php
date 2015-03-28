<?php
namespace Garradin;

require_once __DIR__ . '/../../_inc.php';

if ($user['droits']['membres'] < Membres::DROIT_ECRITURE)
{
    throw new UserException("Vous n'avez pas le droit d'accéder à cette page.");
}

$membre = false;

$cotisations = new Cotisations;
$m_cotisations = new Membres\Cotisations;

if (empty($_GET['id']) || !is_numeric($_GET['id']))
{
    throw new UserException("Argument du numéro de cotisation membre manquant.");
}

$id = (int) $_GET['id'];

$co = $m_cotisations->get($id);

if (!$co)
{
    throw new UserException("Cette cotisation membre n'existe pas.");
}

$membre = $membres->get($co['id_membre']);

if (!$membre)
{
    throw new UserException("Le membre lié à la cotisation n'existe pas ou plus.");
}

$error = false;

if (!empty($_POST['delete']))
{
    if (!Utils::CSRF_check('del_cotisation_' . $co['id']))
    {
        $error = 'Une erreur est survenue, merci de renvoyer le formulaire.';
    }
    else
    {
        try {
            $m_cotisations->delete($co['id']);
            Utils::redirect('/admin/membres/cotisations.php?id=' . $membre['id']);
        }
        catch (UserException $e)
        {
            $error = $e->getMessage();
        }
    }
}

$tpl->assign('error', $error);
$tpl->assign('membre', $membre);
$tpl->assign('cotisation', $co);
$tpl->assign('nb_operations', $m_cotisations->countOperationsCompta($co['id']));

$tpl->display('admin/membres/cotisations/supprimer.tpl');
