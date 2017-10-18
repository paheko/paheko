<?php
namespace Garradin;

require_once __DIR__ . '/../_inc.php';

$session->requireAccess('compta', Membres::DROIT_ADMIN);

$e = new Compta\Exercices;

$exercice = $e->get((int)qg('id'));

if (!$exercice)
{
	throw new UserException('Exercice inconnu.');
}

if ($exercice->cloture)
{
    throw new UserException('Impossible de modifier un exercice clôturé.');
}

if ($form('edit'))
{
    $form->check('compta_modif_exercice_' . $exercice->id, [
        'libelle' => 'required',
        'fin'     => 'required|date',
        'debut'   => 'required|date',
    ]);

    if (!$form->hasErrors())
    {
        try
        {
            $id = $e->edit($exercice->id, [
                'libelle'   =>  f('libelle'),
                'debut'     =>  f('debut'),
                'fin'       =>  f('fin'),
            ]);

            Utils::redirect('/admin/compta/exercices/');
        }
        catch (UserException $e)
        {
            $form->addError($e->getMessage());
        }
    }
}

$tpl->assign('exercice', $exercice);

$tpl->display('admin/compta/exercices/modifier.tpl');
