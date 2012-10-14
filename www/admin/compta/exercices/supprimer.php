<?php

require_once __DIR__ . '/../_inc.php';

if ($user['droits']['compta'] < Garradin_Membres::DROIT_ADMIN)
{
    throw new UserException("Vous n'avez pas le droit d'accéder à cette page.");
}

require_once GARRADIN_ROOT . '/include/class.compta_exercices.php';
$e = new Garradin_Compta_Exercices;

$exercice = $e->get((int)utils::get('id'));

if (!$exercice)
{
	throw new UserException('Exercice inconnu.');
}

$error = false;

if (!empty($_POST['delete']))
{
    if (!utils::CSRF_check('compta_supprimer_exercice_'.$exercice['id']))
    {
        $error = 'Une erreur est survenue, merci de renvoyer le formulaire.';
    }
    else
    {
        try
        {
            $id = $e->delete($exercice['id']);

            utils::redirect('/admin/compta/exercices/');
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