<?php

require_once __DIR__ . '/_inc.php';

if ($user['droits']['compta'] < Garradin_Membres::DROIT_ECRITURE)
{
    throw new UserException("Vous n'avez pas le droit d'accéder à cette page.");
}

require_once GARRADIN_ROOT . '/include/class.compta_journal.php';
$journal = new Garradin_Compta_Journal;

require_once GARRADIN_ROOT . '/include/class.compta_categories.php';
$cats = new Garradin_Compta_Categories;

require_once GARRADIN_ROOT . '/include/class.compta_comptes_bancaires.php';
$banques = new Garradin_Compta_Comptes_Bancaires;

if (isset($_GET['recette']))
    $type = Garradin_Compta_Categories::RECETTES;
elseif (isset($_GET['depense']))
    $type = Garradin_Compta_Categories::DEPENSES;
elseif (isset($_GET['autre']))
    $type = Garradin_Compta_Categories::AUTRES;
else
    $type = null;

$error = false;

if (!empty($_POST['save']))
{
    if (!utils::CSRF_check('compta_saisie'))
    {
        $error = 'Une erreur est survenue, merci de renvoyer le formulaire.';
    }
    else
    {
        try
        {
            if (is_null($type))
            {
                $id = $journal->add(array(
                    'libelle'       =>  utils::post('libelle'),
                    'montant'       =>  utils::post('montant'),
                    'date'          =>  utils::post('date'),
                    'compte_credit' =>  utils::post('compte_credit'),
                    'compte_debit'  =>  utils::post('compte_debit'),
                    'numero_piece'  =>  utils::post('numero_piece'),
                    'remarques'     =>  utils::post('remarques'),
                    'id_auteur'     =>  $user['id'],
                ));
            }
            else
            {
                $cat = $cats->get(utils::post('categorie'));

                if (!$cat)
                {
                    throw new UserException('Il faut choisir une catégorie.');
                }

                if (!array_key_exists(utils::post('moyen_paiement'), $cats->listMoyensPaiement()))
                {
                    throw new UserException('Moyen de paiement invalide.');
                }

                if (utils::post('moyen_paiement') == 'ES')
                {
                    $debit = Garradin_Compta_Comptes::CAISSE;
                    $credit = $cat['compte'];
                }
                else
                {
                    if (!trim(utils::post('banque')))
                    {
                        throw new UserException('Le compte bancaire choisi est invalide.');
                    }

                    if (!array_key_exists(utils::post('banque'), $banques->getList()))
                    {
                        throw new UserException('Le compte bancaire choisi n\'existe pas.');
                    }
                }

                $id = $journal->add(array(
                    'libelle'       =>  utils::post('libelle'),
                    'montant'       =>  utils::post('montant'),
                    'date'          =>  utils::post('date'),
                    'moyen_paiement'=>  utils::post('moyen_paiement'),
                    'numero_cheque' =>  utils::post('numero_cheque'),
                    'compte_credit' =>  $credit,
                    'compte_debit'  =>  $debit,
                    'numero_piece'  =>  utils::post('numero_piece'),
                    'remarques'     =>  utils::post('remarques'),
                    'id_categorie'  =>  (int)$cat['id'],
                    'id_auteur'     =>  $user['id'],
                ));
            }

            utils::redirect('/admin/compta/operation.php?id='.(int)$id);
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
    $tpl->assign('moyen_paiement', utils::post('moyen_paiement'));
    $tpl->assign('categories', $cats->getList($type));
    $tpl->assign('comptes_bancaires', $banques->getList());
    $tpl->assign('banque', utils::post('banque'));
}

$tpl->display('admin/compta/saisie.tpl');

?>