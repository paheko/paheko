<?php
namespace Garradin;

require_once __DIR__ . '/../_inc.php';

$session->requireAccess('compta', Membres::DROIT_ADMIN);

$journal = new Compta\Journal;
$cats = new Compta\Categories;
$banques = new Compta\Comptes_Bancaires;

$operation = $journal->get(qg('id'));

if (!$operation)
{
    throw new UserException("L'opération demandée n'existe pas.");
}

if ($operation->id_categorie)
{
    $categorie = $cats->get($operation->id_categorie);
}
else
{
    $categorie = false;
}

if ($categorie && $categorie->type != Compta\Categories::AUTRES)
{
    $type = $categorie->type;
}
else
{
    $type = null;
}

if (f('save'))
{
    $form->check('compta_modifier_' . $operation->id, [
        'libelle' => 'required|string',
        'montant' => 'required|money',
        'date'    => 'required|date_format:Y-m-d',
    ]);

    if (!$form->hasErrors())
    {
        try
        {
            if (is_null($type))
            {
                $journal->edit($operation->id, [
                    'libelle'       =>  f('libelle'),
                    'montant'       =>  f('montant'),
                    'date'          =>  f('date'),
                    'compte_credit' =>  f('compte_credit'),
                    'compte_debit'  =>  f('compte_debit'),
                    'numero_piece'  =>  f('numero_piece'),
                    'remarques'     =>  f('remarques'),
                    'id_projet'     =>  f('id_projet'),
                ]);
            }
            else
            {
                $cat = $cats->get(f('id_categorie'));

                if (!$cat)
                {
                    throw new UserException('Il faut choisir une catégorie.');
                }

                if (!array_key_exists(f('moyen_paiement'), $cats->listMoyensPaiement()))
                {
                    throw new UserException('Moyen de paiement invalide.');
                }

                if (f('moyen_paiement') == 'ES')
                {
                    $a = Compta\Comptes::CAISSE;
                    $b = $cat->compte;
                }
                else
                {
                    if (!trim(f('banque')))
                    {
                        throw new UserException('Le compte bancaire choisi est invalide.');
                    }

                    if (!array_key_exists(f('banque'), $banques->getList())
                        && f('banque') != Compta\Comptes::CHEQUE_A_ENCAISSER
                        && f('banque') != Compta\Comptes::CARTE_A_ENCAISSER)
                    {
                        throw new UserException('Le compte bancaire choisi n\'existe pas.');
                    }

                    $a = f('banque');
                    $b = $cat->compte;
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

                $journal->edit($operation->id, [
                    'libelle'       =>  f('libelle'),
                    'montant'       =>  f('montant'),
                    'date'          =>  f('date'),
                    'moyen_paiement'=>  f('moyen_paiement'),
                    'numero_cheque' =>  f('numero_cheque'),
                    'compte_credit' =>  $credit,
                    'compte_debit'  =>  $debit,
                    'numero_piece'  =>  f('numero_piece'),
                    'remarques'     =>  f('remarques'),
                    'id_categorie'  =>  (int)$cat->id,
                    'id_projet'     =>  f('id_projet'),
                ]);
            }

            Utils::redirect('/admin/compta/operations/voir.php?id='.(int)$operation->id);
        }
        catch (UserException $e)
        {
            $form->addError($e->getMessage());
        }
    }
}

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

$tpl->assign('projets', (new Compta\Projets)->getAssocList());

$tpl->assign('operation', $operation);

$tpl->display('admin/compta/operations/modifier.tpl');
