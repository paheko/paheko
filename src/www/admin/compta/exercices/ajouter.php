<?php
namespace Garradin;

require_once __DIR__ . '/../_inc.php';

$session->requireAccess('compta', Membres::DROIT_ADMIN);

$e = new Compta\Exercices;

if (f('add'))
{
    $form->check('compta_ajout_exercice', [
        'libelle' => 'required',
        'fin'     => 'required|date',
        'debut'   => 'required|date',
    ]);

    if (!$form->hasErrors())
    {
        try
        {
            $id = $e->add([
                'libelle' =>  f('libelle'),
                'debut'   =>  f('debut'),
                'fin'     =>  f('fin'),
            ]);

            Utils::redirect('/admin/compta/exercices/');
        }
        catch (UserException $e)
        {
            $form->addError($e->getMessage());
        }
    }
}

$tpl->display('admin/compta/exercices/ajouter.tpl');
