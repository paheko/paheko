<?php
namespace Garradin;

require_once __DIR__ . '/../_inc.php';

$session->requireAccess('compta', Membres::DROIT_ADMIN);

$classe = (int) qg('classe');

if (!$classe || $classe < 1 || $classe > 9)
{
    throw new UserException("Cette classe de compte n'existe pas.");
}

if (f('add'))
{
    if ($form->check('compta_ajout_compte'))
    {
        try
        {
            $id = $comptes->add([
                'id'       =>  f('numero'),
                'libelle'  =>  f('libelle'),
                'parent'   =>  f('parent'),
                'position' =>  f('position'),
            ]);

            Utils::redirect('/admin/compta/comptes/?classe='.$classe);
        }
        catch (UserException $e)
        {
            $form->addError($e->getMessage());
        }
    }
}

$parent = $comptes->get(f('parent') ?: $classe);

$tpl->assign('positions', $comptes->getPositions());
$tpl->assign('position', f('position') ?: $parent->position);
$tpl->assign('comptes', $comptes->listTree($classe));

$tpl->display('admin/compta/comptes/ajouter.tpl');
