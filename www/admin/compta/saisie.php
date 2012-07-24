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
}

$tpl->display('admin/compta/saisie.tpl');

?>