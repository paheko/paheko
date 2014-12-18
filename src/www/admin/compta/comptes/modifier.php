<?php
namespace Garradin;

require_once __DIR__ . '/../_inc.php';

if ($user['droits']['compta'] < Membres::DROIT_ADMIN)
{
    throw new UserException("Vous n'avez pas le droit d'accéder à cette page.");
}

$id = Utils::get('id');
$compte = $comptes->get($id);

if (!$compte)
{
    throw new UserException('Le compte demandé n\'existe pas.');
}

$error = false;

if (!empty($_POST['save']))
{
    if (!Utils::CSRF_check('compta_edit_compte_'.$compte['id']))
    {
        $error = 'Une erreur est survenue, merci de renvoyer le formulaire.';
    }
    else
    {
        try
        {
            $id = $comptes->edit($compte['id'], [
                'libelle'       =>  Utils::post('libelle'),
                'position'      =>  Utils::post('position'),
            ]);

            Utils::redirect('/admin/compta/comptes/?classe='.substr($compte['id'], 0, 1));
        }
        catch (UserException $e)
        {
            $error = $e->getMessage();
        }
    }
}

$tpl->assign('error', $error);

$tpl->assign('positions', $comptes->getPositions());
$tpl->assign('position', Utils::post('position') ?: $compte['position']);
$tpl->assign('compte', $compte);

$tpl->display('admin/compta/comptes/modifier.tpl');

?>