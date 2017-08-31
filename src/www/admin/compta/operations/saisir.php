<?php
namespace Garradin;

require_once __DIR__ . '/../_inc.php';

$session->requireAccess('compta', Membres::DROIT_ECRITURE);

$journal = new Compta\Journal;

$journal->checkExercice();

$cats = new Compta\Categories;
$banques = new Compta\Comptes_Bancaires;

if (null !== qg('depense'))
    $type = Compta\Categories::DEPENSES;
elseif (null !== qg('virement'))
    $type = 'virement';
elseif (null !== qg('dette'))
    $type = 'dette';
elseif (null !== qg('avance'))
    $type = null;
else
    $type = Compta\Categories::RECETTES;

if (f('save'))
{
    $form->check('compta_saisie', [
        'libelle' => 'required',
        'date'    => 'date_format:Y-m-d|required',
        'montant' => 'money|required',
    ]);

    if (!$form->hasErrors())
    {
        try
        {
            if (is_null($type))
            {
                $id = $journal->add([
                    'libelle'       =>  f('libelle'),
                    'montant'       =>  f('montant'),
                    'date'          =>  f('date'),
                    'compte_credit' =>  f('compte_credit'),
                    'compte_debit'  =>  f('compte_debit'),
                    'numero_piece'  =>  f('numero_piece'),
                    'remarques'     =>  f('remarques'),
                    'id_auteur'     =>  $user->id,
                ]);
            }
            elseif ($type === 'virement')
            {
                $id = $journal->add([
                    'libelle'       =>  f('libelle'),
                    'montant'       =>  f('montant'),
                    'date'          =>  f('date'),
                    'compte_debit'  =>  f('compte1'),
                    'compte_credit' =>  f('compte2'),
                    'numero_piece'  =>  f('numero_piece'),
                    'remarques'     =>  f('remarques'),
                    'id_auteur'     =>  $user->id,
                ]);
            }
            else
            {
                $cat = $cats->get(f('categorie'));

                if (!$cat)
                {
                    throw new UserException('Il faut choisir une catégorie.');
                }

                if ($type == 'dette')
                {
                    if (!trim(f('compte')) ||
                        (f('compte') != 4010 && f('compte') != 4110))
                    {
                        throw new UserException('Type de dette invalide.');
                    }
                }
                else
                {
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

                        if (!array_key_exists(f('banque'), $banques->getList()))
                        {
                            throw new UserException('Le compte bancaire choisi n\'existe pas.');
                        }

                        $a = f('banque');
                        $b = $cat->compte;
                    }
                }

                if ($type === Compta\Categories::DEPENSES)
                {
                    $debit = $b;
                    $credit = $a;
                }
                elseif ($type === Compta\Categories::RECETTES)
                {
                    $debit = $a;
                    $credit = $b;
                }
                elseif ($type === 'dette')
                {
                    $debit = $cat->compte;
                    $credit = f('compte');
                }

                $id = $journal->add([
                    'libelle'       =>  f('libelle'),
                    'montant'       =>  f('montant'),
                    'date'          =>  f('date'),
                    'moyen_paiement'=>  ($type === 'dette') ? null : f('moyen_paiement'),
                    'numero_cheque' =>  ($type === 'dette') ? null : f('numero_cheque'),
                    'compte_credit' =>  $credit,
                    'compte_debit'  =>  $debit,
                    'numero_piece'  =>  f('numero_piece'),
                    'remarques'     =>  f('remarques'),
                    'id_categorie'  =>  ($type === 'dette') ? null : (int)$cat->id,
                    'id_auteur'     =>  $user->id,
                ]);
            }

            $session->sessionStore('compta_date', f('date'));

            if ($type == Compta\Categories::DEPENSES)
                $type = 'depense';
            elseif (is_null($type))
                $type = 'avance';
            elseif ($type == Compta\Categories::RECETTES)
                $type = 'recette';

            Utils::redirect('/admin/compta/operations/saisir.php?'.$type.'&ok='.(int)$id);
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
    $tpl->assign('moyen_paiement', f('moyen_paiement') ?: 'ES');
    $tpl->assign('categories', $cats->getList($type === 'dette' ? Compta\Categories::DEPENSES : $type));
    $tpl->assign('comptes_bancaires', $banques->getList());
    $tpl->assign('banque', f('banque'));
}

if (!$session->sessionGet('compta_date'))
{
    $exercices = new Compta\Exercices;
    $exercice = $exercices->getCurrent();

    if ($exercice->debut > time() || $exercice->fin < time())
    {
        $session->sessionStore('compta_date', date('Y-m-d', $exercice->debut));
    }
    else
    {
        $session->sessionStore('compta_date', date('Y-m-d'));
    }
}

$tpl->assign('date', $session->sessionGet('compta_date') ?: false);
$tpl->assign('ok', (int) qg('ok'));

$tpl->display('admin/compta/operations/saisir.tpl');
