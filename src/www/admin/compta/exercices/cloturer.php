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

if (f('close'))
{
    $form->check('compta_cloturer_exercice_' . $exercice->id, [
        'fin'     => 'date|required',
        'reports' => 'boolean',
    ]);

    if (!$form->hasErrors())
    {
        try
        {
            $id = $e->close($exercice->id, f('fin'));
        
            if ($id && f('reports'))
            {
                $e->doReports($exercice->id, Utils::modifyDate(f('fin'), '+1 day'));
            }

            Utils::redirect('/admin/compta/exercices/');
        }
        catch (UserException $e)
        {
            $form->addError($e->getMessage());
        }
    }
}

$tpl->assign('exercice', $exercice);

$tpl->display('admin/compta/exercices/cloturer.tpl');
