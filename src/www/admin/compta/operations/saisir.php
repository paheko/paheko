<?php
namespace Garradin;

require_once __DIR__ . '/../_inc.php';

if ($user['droits']['compta'] < Membres::DROIT_ECRITURE)
{
    throw new UserException("Vous n'avez pas le droit d'accéder à cette page.");
}

$journal = new Compta\Journal;

$journal->checkExercice();

$cats = new Compta\Categories;
$banques = new Compta\Comptes_Bancaires;

if (isset($_GET['depense']))
    $type = Compta\Categories::DEPENSES;
elseif (isset($_GET['virement']))
    $type = 'virement';
elseif (isset($_GET['dette']))
    $type = 'dette';
elseif (isset($_GET['avance']))
    $type = null;
else
    $type = Compta\Categories::RECETTES;

$error = false;

if (!empty($_POST['save']))
{
    if (!Utils::CSRF_check('compta_saisie'))
    {
        $error = 'Une erreur est survenue, merci de renvoyer le formulaire.';
    }
    else
    {
        try
        {
            if (is_null($type))
            {
                $id = $journal->add([
                    'libelle'       =>  Utils::post('libelle'),
                    'montant'       =>  Utils::post('montant'),
                    'date'          =>  Utils::post('date'),
                    'compte_credit' =>  Utils::post('compte_credit'),
                    'compte_debit'  =>  Utils::post('compte_debit'),
                    'numero_piece'  =>  Utils::post('numero_piece'),
                    'remarques'     =>  Utils::post('remarques'),
                    'id_auteur'     =>  $user['id'],
                ]);
            }
            elseif ($type === 'virement')
            {
                $id = $journal->add([
                    'libelle'       =>  Utils::post('libelle'),
                    'montant'       =>  Utils::post('montant'),
                    'date'          =>  Utils::post('date'),
                    'compte_debit'  =>  Utils::post('compte1'),
                    'compte_credit' =>  Utils::post('compte2'),
                    'numero_piece'  =>  Utils::post('numero_piece'),
                    'remarques'     =>  Utils::post('remarques'),
                    'id_auteur'     =>  $user['id'],
                ]);
            }
            else
            {
                $cat = $cats->get(Utils::post('categorie'));

                if (!$cat)
                {
                    throw new UserException('Il faut choisir une catégorie.');
                }

                if ($type == 'dette')
                {
                    if (!trim(Utils::post('compte')) ||
                        (Utils::post('compte') != 4010 && Utils::post('compte') != 4110))
                    {
                        throw new UserException('Type de dette invalide.');
                    }
                }
                else
                {
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
                    $debit = $cat['compte'];
                    $credit = Utils::post('compte');
                }

                $id = $journal->add([
                    'libelle'       =>  Utils::post('libelle'),
                    'montant'       =>  Utils::post('montant'),
                    'date'          =>  Utils::post('date'),
                    'moyen_paiement'=>  ($type === 'dette') ? null : Utils::post('moyen_paiement'),
                    'numero_cheque' =>  ($type === 'dette') ? null : Utils::post('numero_cheque'),
                    'compte_credit' =>  $credit,
                    'compte_debit'  =>  $debit,
                    'numero_piece'  =>  Utils::post('numero_piece'),
                    'remarques'     =>  Utils::post('remarques'),
                    'id_categorie'  =>  ($type === 'dette') ? null : (int)$cat['id'],
                    'id_auteur'     =>  $user['id'],
                ]);
            }

            $membres->sessionStore('compta_date', Utils::post('date'));

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
    $tpl->assign('moyen_paiement', Utils::post('moyen_paiement') ?: 'ES');
    $tpl->assign('categories', $cats->getList($type === 'dette' ? Compta\Categories::DEPENSES : $type));
    $tpl->assign('comptes_bancaires', $banques->getList());
    $tpl->assign('banque', Utils::post('banque'));
}

if (!$membres->sessionGet('compta_date'))
{
    $exercices = new Compta\Exercices;
    $exercice = $exercices->getCurrent();

    if ($exercice['debut'] > time() || $exercice['fin'] < time())
    {
        $membres->sessionStore('compta_date', date('Y-m-d', $exercice['debut']));
    }
    else
    {
        $membres->sessionStore('compta_date', date('Y-m-d'));
    }
}

$tpl->assign('date', $membres->sessionGet('compta_date') ?: false);
$tpl->assign('ok', (int) Utils::get('ok'));

$tpl->display('admin/compta/operations/saisir.tpl');
