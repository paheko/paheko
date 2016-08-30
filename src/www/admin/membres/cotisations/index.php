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
	$cats = new Compta\Categories;

	$error = false;

	if (!empty($_POST['save']))
	{
	    if (!Utils::CSRF_check('new_cotisation'))
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

	            $cotisations->add([
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
	$tpl->assign('categories', $cats->getList(Compta\Categories::RECETTES));
}


$tpl->assign('liste', $cotisations->listWithStats());

$tpl->display('admin/membres/cotisations/index.tpl');
