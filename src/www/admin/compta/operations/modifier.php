<?php
namespace Garradin;

require_once __DIR__ . '/../_inc.php';

if ($user['droits']['compta'] < Membres::DROIT_ADMIN)
{
    throw new UserException("Vous n'avez pas le droit d'accéder à cette page.");
}

$journal = new Compta\Journal;
$cats = new Compta\Categories;
$banques = new Compta\Comptes_Bancaires;

$operation = $journal->get(Utils::get('id'));

if (!$operation)
{
    throw new UserException("L'opération demandée n'existe pas.");
}

if ($operation['id_categorie'])
{
    $categorie = $cats->get($operation['id_categorie']);
}
else
{
    $categorie = false;
}

if ($categorie && $categorie['type'] != Compta\Categories::AUTRES)
{
    $type = $categorie['type'];
}
else
{
    $type = null;
}

$error = false;

if (!empty($_POST['save']))
{
    if (!Utils::CSRF_check('compta_modifier_'.$operation['id']))
    {
        $error = 'Une erreur est survenue, merci de renvoyer le formulaire.';
    }
    else
    {
        try
        {
            if (is_null($type))
            {
                $journal->edit($operation['id'], [
                    'libelle'       =>  Utils::post('libelle'),
                    'montant'       =>  Utils::post('montant'),
                    'date'          =>  Utils::post('date'),
                    'compte_credit' =>  Utils::post('compte_credit'),
                    'compte_debit'  =>  Utils::post('compte_debit'),
                    'numero_piece'  =>  Utils::post('numero_piece'),
                    'remarques'     =>  Utils::post('remarques'),
                ]);
            }
            else
            {
                $cat = $cats->get(Utils::post('id_categorie'));

                if (!$cat)
                {
                    throw new UserException('Il faut choisir une catégorie.');
                }

                if (!array_key_exists(Utils::post('moyen_paiement'), $cats->listMoyensPaiement()))
                {
                    throw new UserException('Moyen de paiement invalide.');
                }

                if (Utils::post('moyen_paiement') == 'ES')
                {
                    $a = Compta\Comptes::CAISSE;
                    $b = $cat['compte'];
                }
                else
                {
                    if (!trim(Utils::post('banque')))
                    {
                        throw new UserException('Le compte bancaire choisi est invalide.');
                    }

                    if (!array_key_exists(Utils::post('banque'), $banques->getList()))
                    {
                        throw new UserException('Le compte bancaire choisi n\'existe pas.');
                    }

                    $a = Utils::post('banque');
                    $b = $cat['compte'];
                }

                if ($type == Compta\Categories::DEPENSES)
                {
                    $debit = $b;
                    $credit = $a;
                }
                elseif ($type == Compta\Categories::RECETTES)
                {
                    $debit = $a;
                    $credit = $b;
                }

                $journal->edit($operation['id'], [
                    'libelle'       =>  Utils::post('libelle'),
                    'montant'       =>  Utils::post('montant'),
                    'date'          =>  Utils::post('date'),
                    'moyen_paiement'=>  Utils::post('moyen_paiement'),
                    'numero_cheque' =>  Utils::post('numero_cheque'),
                    'compte_credit' =>  $credit,
                    'compte_debit'  =>  $debit,
                    'numero_piece'  =>  Utils::post('numero_piece'),
                    'remarques'     =>  Utils::post('remarques'),
                    'id_categorie'  =>  (int)$cat['id'],
                ]);
            }

            Utils::redirect('/admin/compta/operations/voir.php?id='.(int)$operation['id']);
        }
        catch (UserException $e)
        {
            $error = $e->getMessage();
        }
    }
}

$tpl->assign('error', $error);

$tpl->assign('type', $type);

if ($type === null)
{
    $tpl->assign('comptes', $comptes->listTree());
}
else
{
    $tpl->assign('moyens_paiement', $cats->listMoyensPaiement());
    $tpl->assign('categories', $cats->getList($type));
    $tpl->assign('comptes_bancaires', $banques->getList());
}

$tpl->assign('operation', $operation);

$tpl->display('admin/compta/operations/modifier.tpl');
