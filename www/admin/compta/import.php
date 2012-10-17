<?php

require_once __DIR__ . '/_inc.php';

if ($user['droits']['compta'] < Garradin_Membres::DROIT_ADMIN)
{
    throw new UserException("Vous n'avez pas le droit d'accéder à cette page.");
}

require_once GARRADIN_ROOT . '/include/class.compta_exercices.php';
$e = new Garradin_Compta_Exercices;

require_once GARRADIN_ROOT . '/include/class.compta_import.php';
$import = new Garradin_Compta_Import;

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
    if (!utils::CSRF_check('compta_import'))
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
            if (utils::post('type') == 'citizen')
            {
                $import->fromCitizen($_FILES['upload']['tmp_name']);
            }
            else
            {
                throw new UserException('Import inconnu.');
            }

            utils::redirect('/admin/compta/');
        }
        catch (UserException $e)
        {
            $error = $e->getMessage();
        }
    }
}

$tpl->assign('error', $error);

$tpl->display('admin/compta/import.tpl');

?>