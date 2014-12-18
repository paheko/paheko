<?php
namespace Garradin;

require_once __DIR__ . '/../_inc.php';

if ($user['droits']['compta'] < Membres::DROIT_ADMIN)
{
    throw new UserException("Vous n'avez pas le droit d'accéder à cette page.");
}

$e = new Compta\Exercices;

$exercice = $e->get((int)Utils::get('id'));

if (!$exercice)
{
	throw new UserException('Exercice inconnu.');
}

if ($exercice['cloture'])
{
    throw new UserException('Impossible de supprimer un exercice clôturé.');
}

$error = false;

if (!empty($_POST['delete']))
{
    if (!Utils::CSRF_check('compta_supprimer_exercice_'.$exercice['id']))
    {
        $error = 'Une erreur est survenue, merci de renvoyer le formulaire.';
    }
    else
    {
        try
        {
            $id = $e->delete($exercice['id']);

            Utils::redirect('/admin/compta/exercices/');
        }
        catch (UserException $e)
        {
            $error = $e->getMessage();
        }
    }
}

$tpl->assign('error', $error);
$tpl->assign('exercice', $exercice);

$tpl->display('admin/compta/exercices/supprimer.tpl');

?>