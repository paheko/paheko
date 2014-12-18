<?php
namespace Garradin;

require_once __DIR__ . '/../../../_inc.php';

if ($user['droits']['membres'] < Membres::DROIT_ADMIN)
{
    throw new UserException("Vous n'avez pas le droit d'accéder à cette page.");
}

if (!Utils::get('id') || !is_numeric(Utils::get('id')))
{
    throw new UserException("Argument du numéro de cotisation manquant.");
}

$cotisations = new Cotisations;

$co = $cotisations->get(Utils::get('id'));
$cats = new Compta\Categories;

if (!$co)
{
    throw new UserException("Cette cotisation n'existe pas.");
}

$error = false;

if (!empty($_POST['save']))
{
    if (!Utils::CSRF_check('edit_co_' . $co['id']))
    {
        $error = 'Une erreur est survenue, merci de renvoyer le formulaire.';
    }
    else
    {
        try {
            $duree = Utils::post('periodicite') == 'jours' ? (int) Utils::post('duree') : null;
            $debut = Utils::post('periodicite') == 'date' ? Utils::post('debut') : null;
            $fin = Utils::post('periodicite') == 'date' ? Utils::post('fin') : null;
            $id_cat = Utils::post('categorie') ? (int) Utils::post('id_categorie_compta') : null;

            $cotisations->edit($co['id'], [
                'intitule'          =>  Utils::post('intitule'),
                'description'       =>  Utils::post('description'),
                'montant'           =>  (float) Utils::post('montant'),
                'duree'             =>  $duree,
                'debut'             =>  $debut,
                'fin'               =>  $fin,
                'id_categorie_compta'=> $id_cat,
            ]);

            Utils::redirect('/admin/membres/cotisations/');
        }
        catch (UserException $e)
        {
            $error = $e->getMessage();
        }
    }
}

$tpl->assign('error', $error);

$co['periodicite'] = $co['duree'] ? 'jours' : ($co['debut'] ? 'date' : 'ponctuel');
$co['categorie'] = $co['id_categorie_compta'] ? 1 : 0;

$tpl->assign('cotisation', $co);
$tpl->assign('categories', $cats->getList(Compta\Categories::RECETTES));

$tpl->display('admin/membres/cotisations/gestion/modifier.tpl');

?>