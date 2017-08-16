<?php
namespace Garradin;

require_once __DIR__ . '/../_inc.php';

$banques = new Compta\Comptes_Bancaires;
$journal = new Compta\Journal;

if (f('add') && $form->check('compta_ajout_banque'))
{
	$session->requireAccess('compta', Membres::DROIT_ADMIN);

    try
    {
        $id = $banques->add([
            'libelle' => f('libelle'),
            'banque'  => f('banque'),
            'iban'    => f('iban'),
            'bic'     => f('bic'),
        ]);

        if (f('solde') > 0)
        {
        	$exercices = new Compta\Exercices;
        	$exercice = $exercices->getCurrent();
        	$solde = f('solde');

        	$journal->add([
                'libelle'       =>  'Solde initial',
                'montant'       =>  abs($solde),
                'date'          =>  gmdate('Y-m-d', $exercice->debut),
                'compte_credit' =>  $solde > 0 ? null : $id,
                'compte_debit'  =>  $solde < 0 ? null : $id,
                'numero_piece'  =>  null,
                'remarques'     =>  'Opération automatique à l\'ajout du compte dans la liste des comptes bancaires',
                'id_auteur'     =>  $user->id,
            ]);
        }

        Utils::redirect('/admin/compta/banques/');
    }
    catch (UserException $e)
    {
        $form->addError($e->getMessage());
    }
}

$liste = $banques->getList();

foreach ($liste as &$banque)
{
    $banque->solde = $journal->getSolde($banque->id);
}

$tpl->assign('liste', $liste);

$tpl->register_modifier('format_iban', function ($iban) {
    return implode(' ', str_split($iban, 4));
});

$tpl->display('admin/compta/banques/index.tpl');
