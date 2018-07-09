<?php
namespace Garradin;

require_once __DIR__ . '/_inc.php';

$session->requireAccess('compta', Membres::DROIT_ADMIN);

$e = new Compta\Exercices;
$import = new Compta\Import;

if (qg('export') == 'csv')
{
    header('Content-type: application/csv');
    header('Content-Disposition: attachment; filename="Export comptabilité - ' . $config->get('nom_asso') . ' - ' . date('Y-m-d') . '.csv"');
    $import->toCSV($e->getCurrentId());
    exit;
}
elseif (qg('export') == 'ods')
{
    header('Content-type: application/vnd.oasis.opendocument.spreadsheet');
    header('Content-Disposition: attachment; filename="Export comptabilité - ' . $config->get('nom_asso') . ' - ' . date('Y-m-d') . '.ods"');
    $import->toODS($e->getCurrentId());
    exit;
}

if (f('import'))
{
    $form->check('compta_import', [
        'upload' => 'file|required',
        'type'   => 'required|in:citizen,garradin',
    ]);

    if (!$form->hasErrors())
    {
        try
        {
            if (f('type') == 'citizen')
            {
                $import->fromCitizen($_FILES['upload']['tmp_name']);
            }
            elseif (f('type') == 'garradin')
            {
                $import->fromCSV($_FILES['upload']['tmp_name']);
            }
            else
            {
                throw new UserException('Import inconnu.');
            }

            Utils::redirect(ADMIN_URL . 'compta/import.php?ok');
        }
        catch (UserException $e)
        {
            $form->addError($e->getMessage());
        }
    }
}

$tpl->assign('ok', qg('ok') !== null);

$tpl->display('admin/compta/import.tpl');
