<?php
namespace Garradin;

require_once __DIR__ . '/../_inc.php';

$session->requireAccess('compta', Membres::DROIT_ECRITURE);

$journal = new Compta\Journal;

$journal->checkExercice();

$cats = new Compta\Categories;
$banques = new Compta\Comptes_Bancaires;

$type = f('type') ?: 'recette';

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
            if ($type == 'avance')
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
                    'id_projet'     =>  f('projet') || null,
                ]);
            }
            elseif ($type == 'virement')
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
                    'id_projet'     =>  f('projet') || null,
                ]);
            }
            else
            {
                if ($type == 'recette')
                {
                    $cat = 'categorie_recette';
                }
                else
                {
                    // Dette ou dépense
                    $cat = 'categorie_depense';
                }

                $cat = $cats->get(f($cat));

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
                    elseif (in_array(f('moyen_paiement'), ['CH', 'CB']) && f('a_encaisser'))
                    {
                        $a = f('moyen_paiement') == 'CH' 
                            ? Compta\Comptes::CHEQUE_A_ENCAISSER
                            : Compta\Comptes::CARTE_A_ENCAISSER;
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

                if ($type == 'depense')
                {
                    $debit = $b;
                    $credit = $a;
                }
                elseif ($type == 'recette')
                {
                    $debit = $a;
                    $credit = $b;
                }
                elseif ($type == 'dette')
                {
                    $debit = $cat->compte;
                    $credit = f('compte');
                }

                $id = $journal->add([
                    'libelle'       =>  f('libelle'),
                    'montant'       =>  f('montant'),
                    'date'          =>  f('date'),
                    'moyen_paiement'=>  ($type == 'dette') ? null : f('moyen_paiement'),
                    'numero_cheque' =>  ($type == 'dette') ? null : f('numero_cheque'),
                    'compte_credit' =>  $credit,
                    'compte_debit'  =>  $debit,
                    'numero_piece'  =>  f('numero_piece'),
                    'remarques'     =>  f('remarques'),
                    'id_categorie'  =>  ($type === 'dette') ? null : (int)$cat->id,
                    'id_auteur'     =>  $user->id,
                    'id_projet'     =>  f('projet') || null,
                ]);
            }

            $session->set('context_compta_date', f('date'));

            Utils::redirect('/admin/compta/operations/saisir.php?ok='.(int)$id);
        }
        catch (UserException $e)
        {
            $form->addError($e->getMessage());
        }
    }
}

$tpl->assign('type', $type);

$tpl->assign('comptes', $comptes->listTree());
$tpl->assign('moyens_paiement', $cats->listMoyensPaiement());
$tpl->assign('moyen_paiement', f('moyen_paiement') ?: 'ES');
$tpl->assign('categories_depenses', $cats->getList(Compta\Categories::DEPENSES));
$tpl->assign('categories_recettes', $cats->getList(Compta\Categories::RECETTES));
$tpl->assign('comptes_bancaires', $banques->getList());
$tpl->assign('banque', f('banque'));
$tpl->assign('compte_cheque_e_encaisser', Compta\Comptes::CHEQUE_A_ENCAISSER);
$tpl->assign('compte_carte_e_encaisser', Compta\Comptes::CARTE_A_ENCAISSER);
$tpl->assign('projets', (new Compta\Projets)->getAssocList());

if (!$session->get('context_compta_date'))
{
    $exercices = new Compta\Exercices;
    $exercice = $exercices->getCurrent();

    if ($exercice->debut > time() || $exercice->fin < time())
    {
        $session->set('context_compta_date', date('Y-m-d', $exercice->debut));
    }
    else
    {
        $session->get('context_compta_date', date('Y-m-d'));
    }
}

$tpl->assign('date', $session->get('context_compta_date') ?: false);
$tpl->assign('ok', (int) qg('ok'));

$tpl->display('admin/compta/operations/saisir.tpl');
