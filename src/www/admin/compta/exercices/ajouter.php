<?php
namespace Garradin;

require_once __DIR__ . '/../_inc.php';

$session->requireAccess('compta', Membres::DROIT_ADMIN);

$e = new Compta\Exercices;

if (f('add'))
{
    fc('compta_ajout_exercice', [
        'libelle' => 'required',
        'fin'     => 'required|date',
        'debut'   => 'required|date',
    ], $form_errors);

    if (count($form_errors) == 0)
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
            $form_errors[] = $e->getMessage();
        }
    }
}

$tpl->display('admin/compta/exercices/ajouter.tpl');
