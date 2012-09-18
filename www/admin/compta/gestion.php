<?php

require_once __DIR__ . '/_inc.php';

require_once GARRADIN_ROOT . '/include/class.compta_categories.php';

$cats = new Garradin_Compta_Categories;
$cat = $type = false;

if (utils::get('cat'))
{
	$cat = $cats->get(utils::get('cat'));

	if (!$cat)
	{
		throw new UserException("La catégorie demandée n'existe pas.");
	}

	$type = $cat['type'];
}
else
{
	$type = isset($_GET['depenses'])
		? Garradin_Compta_Categories::DEPENSES
		: Garradin_Compta_Categories::RECETTES;
}

require_once GARRADIN_ROOT . '/include/class.compta_journal.php';
$journal = new Garradin_Compta_Journal;

$list = $journal->getListForCategory($type, $cat ? $cat['id'] : null);

$tpl->assign('categorie', $cat);
$tpl->assign('journal', $list);
$tpl->assign('type', $type);
$tpl->assign('liste_cats', $cats->getList($type));

$total = 0.0;

foreach ($list as $row)
{
	$total += (float) $row['montant'];
}

$tpl->assign('total', $total);

$tpl->display('admin/compta/gestion.tpl');

?>