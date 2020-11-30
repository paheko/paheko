<?php
namespace Garradin;

require_once __DIR__ . '/_inc.php';

$session->requireAccess('membres', Membres::DROIT_ADMIN);

$import = new Membres\Import;

$tpl->assign('tab', null !== qg('export') ? 'export' : 'import');

if (qg('export') == 'csv')
{
    $import->toCSV();
    exit;
}
elseif (qg('export') == 'ods')
{
    $import->toODS();
    exit;
}

$csv = new CSV_Custom($session, 'users_import');
$champs = $config->get('champs_membres')->getAll();
$csrf_key = 'membres_import';

$columns = [];

foreach ($champs as $key => $config) {
    if (!isset($config->title)) {
        continue;
    }

    $columns[$key] = $config->title;
}

$csv->setColumns($columns);

if (f('cancel')) {
    $csv->clear();
    Utils::redirect(Utils::getSelfURL(false));
}

$form->runIf(f('import') && $csv->loaded(), function () use ($csv, $import, $user) {
    $csv->setTranslationTable(f('translation_table'));
    $csv->skip((int)f('skip_first_line'));
    $import->fromCustomCSV($csv, $user->id);
    $csv->clear();
}, $csrf_key, ADMIN_URL . 'membres/import.php?ok');

$form->runIf(f('import') && f('type') == 'garradin' && !empty($_FILES['upload']['tmp_name']), function () use ($import, $user) {
    $import->fromGarradinCSV($_FILES['upload']['tmp_name'], $user->id);
}, $csrf_key, ADMIN_URL . 'membres/import.php?ok');

$form->runIf(f('import') && f('type') == 'custom' && !empty($_FILES['upload']['tmp_name']), function () use ($csv) {
    $csv->load($_FILES['upload']);
}, $csrf_key, ADMIN_URL . 'membres/import.php');

$tpl->assign('ok', null !== qg('ok') ? true : false);

$tpl->assign(compact('csv', 'csrf_key'));

$tpl->assign('max_upload_size', Utils::getMaxUploadSize());

$tpl->display('admin/membres/import.tpl');
