<?php
namespace Garradin;

require_once __DIR__ . '/_inc.php';

if ($user['droits']['compta'] < Membres::DROIT_ADMIN)
{
    throw new UserException("Vous n'avez pas le droit d'accéder à cette page.");
}

$e = new Compta\Exercices;
$import = new Compta\Import;

if (isset($_GET['export']))
{
    header('Content-type: application/csv');
    header('Content-Disposition: attachment; filename="Export comptabilité - ' . $config->get('nom_asso') . ' - ' . date('Y-m-d') . '.csv"');
    $import->toCSV($e->getCurrentId());
    exit;
}

$error = false;

if (!empty($_POST['import']))
{
    if (!Utils::CSRF_check('compta_import'))
    {
        $error = 'Une erreur est survenue, merci de renvoyer le formulaire.';
    }
    elseif (empty($_FILES['upload']['tmp_name']))
    {
        $error = 'Aucun fichier fourni.';
    }
    else
    {
        try
        {
            if (Utils::post('type') == 'citizen')
            {
                $import->fromCitizen($_FILES['upload']['tmp_name']);
            }
            elseif (Utils::post('type') == 'garradin')
            {
                $import->fromCSV($_FILES['upload']['tmp_name']);
            }
            else
            {
                throw new UserException('Import inconnu.');
            }

            Utils::redirect('/admin/compta/import.php?ok');
        }
        catch (UserException $e)
        {
            $error = $e->getMessage();
        }
    }
}

$tpl->assign('error', $error);
$tpl->assign('ok', isset($_GET['ok']) ? true : false);

$tpl->display('admin/compta/import.tpl');

?>