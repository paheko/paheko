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

if (isset($_GET['depense']))
    $type = Garradin_Compta_Categories::DEPENSES;
elseif (isset($_GET['virement']))
    $type = 'virement';
elseif (isset($_GET['dette']))
    $type = 'dette';
elseif (isset($_GET['avance']))
    $type = null;
else
    $type = Garradin_Compta_Categories::RECETTES;

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
            elseif ($type === 'virement')
            {
                $id = $journal->add(array(
                    'libelle'       =>  utils::post('libelle'),
                    'montant'       =>  utils::post('montant'),
                    'date'          =>  utils::post('date'),
                    'compte_credit' =>  utils::post('compte1'),
                    'compte_debit'  =>  utils::post('compte2'),
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

                if ($type == 'dette')
                {
                    if (!trim(utils::post('compte')) ||
                        (utils::post('compte') != 4010 && utils::post('compte') != 4110))
                    {
                        throw new UserException('Type de dette invalide.');
                    }
                }
                else
                {
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
                }

                if ($type === Garradin_Compta_Categories::DEPENSES)
                {
                    $debit = $b;
                    $credit = $a;
                }
                elseif ($type === Garradin_Compta_Categories::RECETTES)
                {
                    $debit = $a;
                    $credit = $b;
                }
                elseif ($type === 'dette')
                {
                    $debit = $cat['compte'];
                    $credit = utils::post('compte');
                }

                $id = $journal->add(array(
                    'libelle'       =>  utils::post('libelle'),
                    'montant'       =>  utils::post('montant'),
                    'date'          =>  utils::post('date'),
                    'moyen_paiement'=>  ($type === 'dette') ? null : utils::post('moyen_paiement'),
                    'numero_cheque' =>  ($type === 'dette') ? null : utils::post('numero_cheque'),
                    'compte_credit' =>  $credit,
                    'compte_debit'  =>  $debit,
                    'numero_piece'  =>  utils::post('numero_piece'),
                    'remarques'     =>  utils::post('remarques'),
                    'id_categorie'  =>  ($type === 'dette') ? null : (int)$cat['id'],
                    'id_auteur'     =>  $user['id'],
                ));
            }

            $membres->sessionStore('compta_date', utils::post('date'));

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
    $tpl->assign('moyen_paiement', utils::post('moyen_paiement') ?: 'ES');
    $tpl->assign('categories', $cats->getList($type === 'dette' ? Garradin_Compta_Categories::DEPENSES : $type));
    $tpl->assign('comptes_bancaires', $banques->getList());
    $tpl->assign('banque', utils::post('banque'));
}

$tpl->assign('custom_js', array('datepickr.js'));
$tpl->assign('date', $membres->sessionGet('compta_date') ?: false);

$tpl->display('admin/compta/saisie.tpl');

?>