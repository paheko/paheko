<?php
namespace Garradin;

require_once __DIR__ . '/../../_inc.php';

if ($user['droits']['membres'] < Membres::DROIT_ECRITURE)
{
    throw new UserException("Vous n'avez pas le droit d'accéder à cette page.");
}

$membre = false;

if (!empty($_GET['id']) && is_numeric($_GET['id']))
{
    $membre = $membres->get((int) $_GET['id']);

    if (!$membre)
    {
        throw new UserException("Ce membre n'existe pas.");
    }
}

$transactions = new Transactions;
$m_transactions = new Membres_Transactions;

$cats = new Compta_Categories;
$banques = new Compta_Comptes_Bancaires;

$error = false;

if (!empty($_POST['add']))
{
    if (!utils::CSRF_check('add_transaction'))
    {
        $error = 'Une erreur est survenue, merci de renvoyer le formulaire.';
    }
    else
    {
        try {
            $data = [
                'libelle'           =>  utils::post('libelle'),
                'date'              =>  utils::post('date'),
                'id_transaction'    =>  utils::post('id_transaction'),
                'montant'           =>  (float) utils::post('montant'),
                'id_membre'         =>  utils::post('id_membre'),
            ];

            if (!empty($data['id_transaction']))
            {
                $data['id_auteur'] = $user['id'];
                $data['moyen_paiement'] = utils::post('moyen_paiement');
                $data['numero_cheque'] = utils::post('numero_cheque');
                $data['banque'] = utils::post('banque');
            }

            $m_transactions->add($data);

            utils::redirect('/admin/membres/transactions.php?id=' . (int)utils::post('id_membre'));
        }
        catch (UserException $e)
        {
            $error = $e->getMessage();
        }
    }
}

$tpl->assign('error', $error);
$tpl->assign('membre', $membre);

$tpl->assign('transactions', $transactions->listCurrent());

$tpl->assign('default_tr', null);
$tpl->assign('default_amount', 0.00);

$tpl->assign('moyens_paiement', $cats->listMoyensPaiement());
$tpl->assign('moyen_paiement', utils::post('moyen_paiement') ?: 'ES');
$tpl->assign('comptes_bancaires', $banques->getList());
$tpl->assign('banque', utils::post('banque'));


if ($membre)
{
    $cats = new Membres_Categories;
    $categorie = $cats->get($membre['id_categorie']);

    if (!empty($categorie['id_transaction_obligatoire']))
    {
        $tr = $transactions->get($categorie['id_transaction_obligatoire']);

        $tpl->assign('default_tr', $tr['id']);
        $tpl->assign('default_amount', $tr['montant']);
    }
}

$tpl->display('admin/membres/transactions/ajout.tpl');

?>