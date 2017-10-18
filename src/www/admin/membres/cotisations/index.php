<?php
namespace Garradin;

require_once __DIR__ . '/../_inc.php';

$session->requireAccess('membres', Membres::DROIT_ECRITURE);

$cotisations = new Cotisations;

if ($session->canAccess('membres', Membres::DROIT_ADMIN))
{
	$cats = new Compta\Categories;

	if (f('save'))
	{
		$form->check('new_cotisation', [
			'intitule'            => 'required|string',
			'montant'             => 'required|money',
			'periodicite'         => 'required|in:jours,date,ponctuel',
			'duree'               => 'required_if:periodicite,jours|numeric|min:0',
			'debut'               => 'required_if:periodicite,date|date_format:Y-m-d',
			'fin'                 => 'required_if:periodicite,date|date_format:Y-m-d',
			'categorie'           => 'boolean',
			'id_categorie_compta' => 'required_if:categorie,1|numeric|in_table:compta_categories,id',
		]);

		if (!$form->hasErrors())
		{
			try {
				$duree = f('periodicite') == 'jours' ? (int) f('duree') : null;
				$debut = f('periodicite') == 'date' ? f('debut') : null;
				$fin = f('periodicite') == 'date' ? f('fin') : null;
				$id_cat = f('categorie') ? (int) f('id_categorie_compta') : null;

				$cotisations->add([
					'intitule'          =>  f('intitule'),
					'description'       =>  f('description'),
					'montant'           =>  (float) f('montant'),
					'duree'             =>  $duree,
					'debut'             =>  $debut,
					'fin'               =>  $fin,
					'id_categorie_compta'=> $id_cat,
				]);

				Utils::redirect('/admin/membres/cotisations/');
			}
			catch (UserException $e)
			{
				$form->addError($e->getMessage());
			}
		}
	}

	$tpl->assign('categories', $cats->getList(Compta\Categories::RECETTES));
}

$tpl->assign('liste', $cotisations->listWithStats());

$tpl->display('admin/membres/cotisations/index.tpl');
