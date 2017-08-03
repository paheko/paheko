<?php
namespace Garradin;

require_once __DIR__ . '/_inc.php';

$session->requireAccess('membres', Membres::DROIT_ADMIN);

$import = new Membres\Import;

if (null !== qg('export'))
{
    header('Content-type: application/csv');
    header('Content-Disposition: attachment; filename="Export membres - ' . $config->get('nom_asso') . ' - ' . date('Y-m-d') . '.csv"');
    $import->toCSV();
    exit;
}

$champs = $config->get('champs_membres')->getAll();
$champs->date_inscription = (object) ['title' => 'Date inscription', 'type' => 'date'];

if (f('import'))
{
    $form->check('membres_import', [
        'upload' => 'file|required',
        'type'   => 'required|in:galette,garradin',
        'galette_translate' => 'array',
    ]);

    if (!$form->hasErrors())
    {
        try
        {
            if (f('type') == 'galette')
            {
                $import->fromGalette($_FILES['upload']['tmp_name'], f('galette_translate'));
            }
            elseif (f('type') == 'garradin')
            {
                $import->fromCSV($_FILES['upload']['tmp_name']);
            }
            else
            {
                throw new UserException('Import inconnu.');
            }

            Utils::redirect('/admin/membres/import.php?ok');
        }
        catch (UserException $e)
        {
            $form->addError($e->getMessage());
        }
    }
}

$tpl->assign('ok', null !== qg('ok') ? true : false);

$tpl->assign('garradin_champs', $champs);
$tpl->assign('galette_champs', $import->galette_fields);
$tpl->assign('translate', f('galette_translate'));

$tpl->display('admin/membres/import.tpl');
