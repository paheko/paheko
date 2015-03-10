<?php
namespace Garradin;

require_once __DIR__ . '/../_inc.php';

$banques = new Compta\Comptes_Bancaires;
$journal = new Compta\Journal;

$error = false;

if (Utils::post('add'))
{
	if ($user['droits']['compta'] < Membres::DROIT_ADMIN)
	{
	    throw new UserException("Vous n'avez pas le droit d'accéder à cette page.");
	}

    if (!Utils::CSRF_check('compta_ajout_banque'))
    {
        $error = 'Une erreur est survenue, merci de renvoyer le formulaire.';
    }
    else
    {
        try
        {
            $id = $banques->add([
                'libelle'       =>  Utils::post('libelle'),
                'banque'        =>  Utils::post('banque'),
                'iban'          =>  Utils::post('iban'),
                'bic'           =>  Utils::post('bic'),
            ]);

            if (Utils::post('solde') > 0)
            {
            	$exercices = new Compta\Exercices;
            	$exercice = $exercices->getCurrent();
            	$solde = Utils::post('solde');

            	$journal->add([
                    'libelle'       =>  'Solde initial',
                    'montant'       =>  abs($solde),
                    'date'          =>  gmdate('Y-m-d', $exercice['debut']),
                    'compte_credit' =>  $solde > 0 ? null : $id,
                    'compte_debit'  =>  $solde < 0 ? null : $id,
                    'numero_piece'  =>  null,
                    'remarques'     =>  'Opération automatique à l\'ajout du compte dans la liste des comptes bancaires',
                    'id_auteur'     =>  $user['id'],
                ]);
            }

            Utils::redirect('/admin/compta/banques/');
        }
        catch (UserException $e)
        {
            $error = $e->getMessage();
        }
	}
}

$liste = $banques->getList();

foreach ($liste as &$banque)
{
    $banque['solde'] = $journal->getSolde($banque['id']);
}

$tpl->assign('liste', $liste);

function tpl_format_iban($iban)
{
    return implode(' ', str_split($iban, 4));
}

$tpl->register_modifier('format_iban', 'Garradin\tpl_format_iban');
$tpl->assign('error', $error);

$tpl->display('admin/compta/banques/index.tpl');
