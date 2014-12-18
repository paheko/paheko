<?php
namespace Garradin;

require_once __DIR__ . '/../_inc.php';

if ($user['droits']['compta'] < Membres::DROIT_ADMIN)
{
    throw new UserException("Vous n'avez pas le droit d'accéder à cette page.");
}

$classe = (int) Utils::get('classe');

if (!$classe || $classe < 1 || $classe > 9)
{
    throw new UserException("Cette classe de compte n'existe pas.");
}

$error = false;

if (!empty($_POST['add']))
{
    if (!Utils::CSRF_check('compta_ajout_compte'))
    {
        $error = 'Une erreur est survenue, merci de renvoyer le formulaire.';
    }
    else
    {
        try
        {
            $id = $comptes->add([
                'id'            =>  Utils::post('numero'),
                'libelle'       =>  Utils::post('libelle'),
                'parent'        =>  Utils::post('parent'),
                'position'      =>  Utils::post('position'),
            ]);

            Utils::redirect('/admin/compta/comptes/?classe='.$classe);
        }
        catch (UserException $e)
        {
            $error = $e->getMessage();
        }
    }
}

$tpl->assign('error', $error);

$parent = $comptes->get(Utils::post('parent') ?: $classe);

$tpl->assign('positions', $comptes->getPositions());
$tpl->assign('position', Utils::post('position') ?: $parent['position']);
$tpl->assign('comptes', $comptes->listTree($classe));

$tpl->display('admin/compta/comptes/ajouter.tpl');

?>