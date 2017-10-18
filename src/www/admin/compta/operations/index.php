<?php
namespace Garradin;

require_once __DIR__ . '/../_inc.php';

$cats = new Compta\Categories;
$cat = $type = false;

if (qg('cat'))
{
	$cat = $cats->get(qg('cat'));

	if (!$cat)
	{
		throw new UserException("La catégorie demandée n'existe pas.");
	}

	$type = $cat->type;
}
else
{
	if (null !== qg('autres'))
		$type = Compta\Categories::AUTRES;
	elseif (null !== qg('depenses'))
		$type = Compta\Categories::DEPENSES;
	else
		$type = Compta\Categories::RECETTES;
}

$journal = new Compta\Journal;

$list = $journal->getListForCategory($type === Compta\Categories::AUTRES ? null : $type, $cat ? $cat->id : null);

$tpl->assign('categorie', $cat);
$tpl->assign('journal', $list);
$tpl->assign('type', $type);

if ($type !== Compta\Categories::AUTRES)
{
	$tpl->assign('liste_cats', $cats->getList($type));
}

$total = 0.0;

foreach ($list as $row)
{
	$total += (float) $row->montant;
}

$tpl->assign('total', $total);

$tpl->display('admin/compta/operations/index.tpl');
