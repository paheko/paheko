<?php
namespace Garradin;

require_once __DIR__ . '/../_inc.php';

if ($user['droits']['compta'] < Membres::DROIT_ADMIN)
{
    throw new UserException("Vous n'avez pas le droit d'accéder à cette page.");
}

$classe = (int) utils::get('classe');

if (!$classe || $classe < 1 || $classe > 9)
{
    throw new UserException("Cette classe de compte n'existe pas.");
}

$error = false;

if (!empty($_POST['add']))
{
    if (!utils::CSRF_check('compta_ajout_compte'))
    {
        $error = 'Une erreur est survenue, merci de renvoyer le formulaire.';
    }
    else
    {
        try
        {
            $id = $comptes->add([
                'id'            =>  utils::post('numero'),
                'libelle'       =>  utils::post('libelle'),
                'parent'        =>  utils::post('parent'),
                'position'      =>  utils::post('position'),
            ]);

            utils::redirect('/admin/compta/comptes/?classe='.$classe);
        }
        catch (UserException $e)
        {
            $error = $e->getMessage();
        }
    }
}

$tpl->assign('error', $error);

$parent = $comptes->get(utils::post('parent') ?: $classe);

$tpl->assign('positions', $comptes->getPositions());
$tpl->assign('position', utils::post('position') ?: $parent['position']);
$tpl->assign('comptes', $comptes->listTree($classe));

$tpl->display('admin/compta/comptes/ajouter.tpl');

?>