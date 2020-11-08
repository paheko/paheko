<?php
namespace Garradin;

use Garradin\Accounting\Transactions;
use Garradin\Services\Services_User;

require_once __DIR__ . '/_inc.php';

qv(['id' => 'required|numeric']);

$id = (int) qg('id');

$membre = $membres->get($id);

if (!$membre)
{
    throw new UserException("Ce membre n'existe pas.");
}

$champs = $config->get('champs_membres');
$tpl->assign('champs', $champs->getList());

$cats = new Membres\Categories;

$categorie = $cats->get($membre->id_categorie);
$tpl->assign('categorie', $categorie);

$tpl->assign('services', Services_User::listDistinctForUser($membre->id));

if ($session->canAccess('compta', Membres::DROIT_ACCES)) {
	$tpl->assign('transactions_linked', Transactions::countForUser($membre->id));
	$tpl->assign('transactions_created', Transactions::countForCreator($membre->id));
}

$tpl->assign('membre', $membre);

$tpl->display('admin/membres/fiche.tpl');
