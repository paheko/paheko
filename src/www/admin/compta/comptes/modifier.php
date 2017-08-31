<?php
namespace Garradin;

require_once __DIR__ . '/../_inc.php';

$session->requireAccess('compta', Membres::DROIT_ADMIN);

$id = qg('id');
$compte = $comptes->get($id);

if (!$compte)
{
    throw new UserException('Le compte demandé n\'existe pas.');
}

if (f('save'))
{
    if ($form->check('compta_edit_compte_' . $compte->id))
    {
        try
        {
            $id = $comptes->edit($compte->id, [
                'libelle'  =>  f('libelle'),
                'position' =>  f('position'),
            ]);

            Utils::redirect('/admin/compta/comptes/?classe='.substr($compte->id, 0, 1));
        }
        catch (UserException $e)
        {
            $form->addError($e->getMessage());
        }
    }
}

$tpl->assign('positions', $comptes->getPositions());
$tpl->assign('position', f('position') ?: $compte->position);
$tpl->assign('compte', $compte);

$tpl->display('admin/compta/comptes/modifier.tpl');
