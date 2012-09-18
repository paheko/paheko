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

$operation = $journal->get(utils::get('id'));

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

if ($categorie && $categorie['type'] != Garradin_Compta_Categories::AUTRES)
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
    if (!utils::CSRF_check('compta_modifier_'.$operation['id']))
    {
        $error = 'Une erreur est survenue, merci de renvoyer le formulaire.';
    }
    else
    {
        try
        {
            if (is_null($type))
            {
                $journal->edit($operation['id'], array(
                    'libelle'       =>  utils::post('libelle'),
                    'montant'       =>  utils::post('montant'),
                    'date'          =>  utils::post('date'),
                    'compte_credit' =>  utils::post('compte_credit'),
                    'compte_debit'  =>  utils::post('compte_debit'),
                    'numero_piece'  =>  utils::post('numero_piece'),
                    'remarques'     =>  utils::post('remarques'),
                ));
            }
            else
            {
                $cat = $cats->get(utils::post('id_categorie'));

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
                    $a = Garradin_Compta_Comptes::CAISSE;
                    $b = $cat['compte'];
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

                    $a = utils::post('banque');
                    $b = $cat['compte'];
                }

                if ($type == Garradin_Compta_Categories::DEPENSES)
                {
                    $debit = $b;
                    $credit = $a;
                }
                elseif ($type == Garradin_Compta_Categories::RECETTES)
                {
                    $debit = $a;
                    $credit = $b;
                }

                $journal->edit($operation['id'], array(
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
                ));
            }

            utils::redirect('/admin/compta/operation.php?id='.(int)$operation['id']);
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

$tpl->assign('custom_js', array('datepickr.js'));

$tpl->assign('operation', $operation);

$tpl->display('admin/compta/operation_modifier.tpl');

?>