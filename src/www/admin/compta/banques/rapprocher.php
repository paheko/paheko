<?php
namespace Garradin;

require_once __DIR__ . '/../_inc.php';

if ($user['droits']['compta'] < Membres::DROIT_ECRITURE)
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

$solde_initial = $solde_final = 0;

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
    $fin = date('Y-m-t', $date);
}

$journal = $rapprochement->getJournal($compte['id'], $debut, $fin, $solde_initial, $solde_final);

// Enregistrement des cases cochées
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
            $rapprochement->record($compte['id'], $journal, Utils::post('rapprocher'), $user['id']);
            Utils::redirect(Utils::getSelfURL());
        }
        catch (UserException $e)
        {
            $error = $e->getMessage();
        }
	}
}

if (substr($debut, 0, 7) == substr($fin, 0, 7))
{
    $tpl->assign('prev', Utils::modifyDate($debut, '-1 month', true));
    $tpl->assign('next', Utils::modifyDate($fin, '+1 month', true));
}

$tpl->assign('compte', $compte);
$tpl->assign('debut', $debut);
$tpl->assign('fin', $fin);

$tpl->assign('journal', $journal);

$tpl->assign('solde_initial', $solde_initial);
$tpl->assign('solde_final', $solde_final);

$tpl->assign('error', $error);

$tpl->display('admin/compta/banques/rapprocher.tpl');
