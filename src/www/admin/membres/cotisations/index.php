<?php
namespace Garradin;

require_once __DIR__ . '/../../_inc.php';

if ($user['droits']['membres'] < Membres::DROIT_ECRITURE)
{
    throw new UserException("Vous n'avez pas le droit d'accéder à cette page.");
}

$cotisations = new Cotisations;

if ($user['droits']['membres'] >= Membres::DROIT_ADMIN)
{
	$cats = new Compta_Categories;

	$error = false;

	if (!empty($_POST['save']))
	{
	    if (!utils::CSRF_check('new_cotisation'))
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

	            $cotisations->add([
	                'intitule'          =>  utils::post('intitule'),
	                'description'       =>  utils::post('description'),
	                'montant'           =>  (float) utils::post('montant'),
	                'duree'             =>  $duree,
	                'debut'             =>  $debut,
	                'fin'               =>  $fin,
	                'id_categorie_compta'=> $id_cat,
	            ]);

	            utils::redirect('/admin/membres/cotisations/');
	        }
	        catch (UserException $e)
	        {
	            $error = $e->getMessage();
	        }
	    }
	}

	$tpl->assign('error', $error);
	$tpl->assign('categories', $cats->getList(Compta_Categories::RECETTES));
}


$tpl->assign('liste', $cotisations->listCurrentWithStats());

$tpl->display('admin/membres/cotisations/index.tpl');

?>