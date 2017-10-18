<?php
namespace Garradin;

require_once __DIR__ . '/../_inc.php';

$session->requireAccess('compta', Membres::DROIT_ECRITURE);

$banques = new Compta\Comptes_Bancaires;
$rapprochement = new Compta\Rapprochement;

$compte = $banques->get(qg('id'));

if (!$compte)
{
    throw new UserException("Le compte demandé n'existe pas.");
}

$solde_initial = $solde_final = 0;

$debut = qg('debut');
$fin = qg('fin');

if ($debut && $fin)
{
    if (!Utils::checkDate($debut) || !Utils::checkDate($fin))
    {
        $form->addError('La date donnée est invalide.');
        $debut = $fin = false;
    }
}

if (!$debut || !$fin)
{
    $debut = date('Y-m-01');
    $fin = date('Y-m-t');
}

$journal = $rapprochement->getJournal($compte->id, $debut, $fin, $solde_initial, $solde_final, (bool) qg('sauf'));

// Enregistrement des cases cochées
if (f('save') && $form->check('compta_rapprocher_' . $compte->id))
{
    try
    {
        $rapprochement->record($compte->id, $journal, f('rapprocher'), $user->id);
        Utils::redirect(Utils::getSelfURL());
    }
    catch (UserException $e)
    {
        $form->addError($e->getMessage());
    }
}

if (substr($debut, 0, 7) == substr($fin, 0, 7))
{
    $tpl->assign('prev', Utils::modifyDate($debut, '-1 month', true));
    $tpl->assign('next', Utils::modifyDate($debut, '+1 month', true));
}

$tpl->assign('compte', $compte);
$tpl->assign('debut', $debut);
$tpl->assign('fin', $fin);

$tpl->assign('journal', $journal);

$tpl->assign('solde_initial', $solde_initial);
$tpl->assign('solde_final', $solde_final);

$tpl->display('admin/compta/banques/rapprocher.tpl');
