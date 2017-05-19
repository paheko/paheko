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
    fc('compta_cloturer_exercice_' . $exercice->id, [
        'fin'     => 'date|required',
        'reports' => 'boolean',
    ], $form_errors);

    if (count($form_errors) == 0)
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
            $form_errors[] = $e->getMessage();
        }
    }
}

$tpl->assign('exercice', $exercice);

$tpl->display('admin/compta/exercices/cloturer.tpl');
