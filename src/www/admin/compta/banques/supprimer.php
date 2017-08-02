<?php
namespace Garradin;

require_once __DIR__ . '/../_inc.php';

$session->requireAccess('compta', Membres::DROIT_ADMIN);

$banque = new Compta\Comptes_Bancaires;

$compte = $banque->get(qg('id'));

if (!$compte)
{
    throw new UserException('Le compte demandé n\'existe pas.');
}

$error = false;

if (!empty($_POST['delete']))
{
    if (!Utils::CSRF_check('compta_delete_banque_'.$compte['id']))
    {
        $error = 'Une erreur est survenue, merci de renvoyer le formulaire.';
    }
    else
    {
        try
        {
            $banque->delete($compte['id']);
            Utils::redirect('/admin/compta/banques/');
        }
        catch (UserException $e)
        {
            $error = $e->getMessage();
        }
    }
}

$tpl->assign('error', $error);

$tpl->assign('compte', $compte);

$tpl->display('admin/compta/banques/supprimer.tpl');
