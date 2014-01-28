<?php
namespace Garradin;

require_once __DIR__ . '/../../../_inc.php';

if ($user['droits']['membres'] < Membres::DROIT_ADMIN)
{
    throw new UserException("Vous n'avez pas le droit d'accéder à cette page.");
}

$transactions = new Transactions;

$tr = $transactions->get(utils::get('id'));
$cats = new Compta_Categories;

if (!$tr)
{
    throw new UserException("Cette transaction n'existe pas.");
}

$error = false;

if (!empty($_POST['save']))
{
    if (!utils::CSRF_check('edit_tr_' . $tr['id']))
    {
        $error = 'Une erreur est survenue, merci de renvoyer le formulaire.';
    }
    else
    {
        try {
            $duree = utils::post('periodicite') == 'jours' ? (int) utils::post('duree') : null;
            $debut = utils::post('periodicite') == 'date' ? utils::post('debut') : null;
            $fin = utils::post('periodicite') == 'date' ? utils::post('fin') : null;
            $id_cat = utils::post('categorie') ? (int) utils::post('id_categorie_compta') : null;

            $transactions->edit($tr['id'], array(
                'intitule'          =>  utils::post('intitule'),
                'description'       =>  utils::post('description'),
                'montant'           =>  (float) utils::post('montant'),
                'duree'             =>  $duree,
                'debut'             =>  $debut,
                'fin'               =>  $fin,
                'id_categorie_compta'=> $id_cat,
            ));

            utils::redirect('/admin/membres/transactions/gestion/');
        }
        catch (UserException $e)
        {
            $error = $e->getMessage();
        }
    }
}

$tpl->assign('error', $error);

$tr['periodicite'] = $tr['duree'] ? 'jours' : ($tr['debut'] ? 'date' : 'ponctuel');
$tr['categorie'] = $tr['id_categorie_compta'] ? 1 : 0;

$tpl->assign('transaction', $tr);
$tpl->assign('categories', $cats->getList(Compta_Categories::RECETTES));

$tpl->display('admin/membres/transactions/gestion/modifier.tpl');

?>