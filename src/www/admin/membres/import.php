<?php
namespace Garradin;

require_once __DIR__ . '/_inc.php';

$session->requireAccess('membres', Membres::DROIT_ADMIN);

$import = new Membres\Import;

$tpl->assign('tab', null !== qg('export') ? 'export' : 'import');

if (qg('export') == 'csv')
{
    header('Content-type: application/csv');
    header('Content-Disposition: attachment; filename="Export membres - ' . $config->get('nom_asso') . ' - ' . date('Y-m-d') . '.csv"');
    $import->toCSV();
    exit;
}
elseif (qg('export') == 'ods')
{
    header('Content-type: application/vnd.oasis.opendocument.spreadsheet');
    header('Content-Disposition: attachment; filename="Export membres - ' . $config->get('nom_asso') . ' - ' . date('Y-m-d') . '.ods"');
    $import->toODS();
    exit;
}

$champs = $config->get('champs_membres')->getAll();
$champs->date_inscription = (object) ['title' => 'Date inscription', 'type' => 'date'];

$csv_file = false;

if (f('csv_encoded'))
{
    $form->check('membres_import', [
        'csv_encoded'     => 'required|json',
        'csv_translate'   => 'required|array',
        'skip_first_line' => 'boolean',
    ]);

    $csv_file = json_decode(f('csv_encoded'), true);

    if (!$form->hasErrors())
    {
        try
        {
            $import->fromArray($csv_file, f('csv_translate'), f('skip_first_line') ? 1 : 0);
            Utils::redirect(ADMIN_URL . 'membres/import.php?ok');
        }
        catch (UserException $e)
        {
            $form->addError($e->getMessage());
        }
    }
}
elseif (f('import'))
{
    $form->check('membres_import', [
        'upload' => 'file|required',
        'type'   => 'required|in:csv,garradin',
    ]);

    if (!$form->hasErrors())
    {
        try
        {
            if (f('type') == 'garradin')
            {
                $import->fromGarradinCSV($_FILES['upload']['tmp_name'], $user->id);
                Utils::redirect(ADMIN_URL . 'membres/import.php?ok');
            }
            elseif (f('type') == 'csv')
            {
                $csv_file = $import->getCSVAsArray($_FILES['upload']['tmp_name']);
            }
            else
            {
                throw new UserException('Import inconnu.');
            }
        }
        catch (UserException $e)
        {
            $form->addError($e->getMessage());
        }
    }
}

$tpl->assign('ok', null !== qg('ok') ? true : false);

$tpl->assign('csv_file', $csv_file);
$tpl->assign('csv_first_line', $csv_file ? reset($csv_file) : null);

$tpl->assign('garradin_champs', $champs);

$tpl->display('admin/membres/import.tpl');
