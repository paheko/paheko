<?php
namespace Garradin;

require_once __DIR__ . '/../_inc.php';

if ($user['droits']['compta'] < Membres::DROIT_ADMIN)
{
    throw new UserException("Vous n'avez pas le droit d'accéder à cette page.");
}

$banques = new Compta\Comptes_Bancaires;
$rapprochement = new Compta\Rapprochement;
$exercices = new Compta\Exercices;
$exercice = $exercices->getCurrent();

$compte = $banques->get(Utils::get('id'));

if (!$compte)
{
    throw new UserException("Le compte demandé n'existe pas.");
}

$error = false;

if (Utils::post('save'))
{
    if (!Utils::CSRF_check('compta_rapprocher_' . $compte['id']))
    {
        $error = 'Une erreur est survenue, merci de renvoyer le formulaire.';
    }
    else
    {
        try
        {
            $rapprochement->record($compte['id'], Utils::post('rapprocher'), $user['id']);
            Utils::redirect('/admin/compta/banques/rapprocher.php?id=');
        }
        catch (UserException $e)
        {
            $error = $e->getMessage();
        }
	}
}

$debut = Utils::get('debut');
$fin = Utils::get('fin');

if ($debut && $fin)
{
    if (!Utils::checkDate($debut) || !Utils::checkDate($fin))
    {
        $error = 'La date donnée est invalide.';
        $debut = $fin = false;
    }
    else if (strtotime($debut) < $exercice['debut'])
    {
        $debut = date('Y-m-d', $exercice['debut']);
    }
    else if (strtotime($fin) > $exercice['fin'])
    {
        $fin = date('Y-m-d', $exercice['fin']);
    }
}

if (!$debut || !$fin)
{
    $date = $exercice['fin'];
    $debut = date('Y-m-01', $date);
    $fin = date('Y-m-31', $date);
}

$tpl->assign('compte', $compte);
$tpl->assign('debut', $debut);
$tpl->assign('fin', $fin);
$tpl->assign('journal', $rapprochement->getJournal($compte['id'], $debut, $fin));

$tpl->assign('error', $error);

$tpl->display('admin/compta/banques/rapprocher.tpl');
