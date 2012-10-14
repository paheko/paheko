<?php

require_once __DIR__ . '/../_inc.php';

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
	if (isset($_GET['autres']))
		$type = Garradin_Compta_Categories::AUTRES;
	elseif (isset($_GET['depenses']))
		$type = Garradin_Compta_Categories::DEPENSES;
	else
		$type = Garradin_Compta_Categories::RECETTES;
}

require_once GARRADIN_ROOT . '/include/class.compta_journal.php';
$journal = new Garradin_Compta_Journal;

$list = $journal->getListForCategory($type === Garradin_Compta_Categories::AUTRES ? null : $type, $cat ? $cat['id'] : null);

$tpl->assign('categorie', $cat);
$tpl->assign('journal', $list);
$tpl->assign('type', $type);

if ($type !== Garradin_Compta_Categories::AUTRES)
{
	$tpl->assign('liste_cats', $cats->getList($type));
}

$total = 0.0;

foreach ($list as $row)
{
	$total += (float) $row['montant'];
}

$tpl->assign('total', $total);

$tpl->display('admin/compta/operations/index.tpl');

?>