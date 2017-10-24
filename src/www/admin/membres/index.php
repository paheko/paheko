<?php
namespace Garradin;

require_once __DIR__ . '/_inc.php';

// Recherche de membre (pour ceux qui n'ont qu'un accès à la liste des membres)
if (qg('r'))
{
	$recherche = trim(qg('r'));

	$result = $membres->search($config->get('champ_identite'), $recherche);
    $tpl->assign('liste', $result);
	$tpl->assign('recherche', $recherche);
}
else
{
	$cats = new Membres\Categories;
	$champs = $config->get('champs_membres');

	$membres_cats = $cats->listSimple();
	$membres_cats_cachees = $cats->listHidden();

	$cat_id = (int) qg('cat') ?: 0;
	$page = (int) qg('p') ?: 1;

	if ($cat_id)
	{
	    if (!$session->canAccess('membres', Membres::DROIT_ECRITURE) && array_key_exists($cat_id, $membres_cats_cachees))
	    {
	    	$cat_id = 0;
	    }
	}

	if (!$cat_id)
	{
	    $cat_id = array_diff(array_keys((array) $membres_cats), array_keys((array) $membres_cats_cachees));
	}

	// Par défaut le champ de tri c'est l'identité
	$order = $config->get('champ_identite');
	$desc = false;

	if (qg('o'))
	    $order = qg('o');

	if (null !== qg('d'))
	    $desc = true;

	$fields = $champs->getListedFields();

	// Vérifier que le champ de tri existe bien dans la table
	if (!isset($fields->$order))
	{
		// Sinon par défaut c'est le premier champ de la table qui fait le tri
		$order = $champs->getFirstListed();
	}

	$tpl->assign('order', $order);
	$tpl->assign('desc', $desc);

	$tpl->assign('champs', $fields);

	$tpl->assign('liste', $membres->listByCategory($cat_id, array_keys((array) $fields), $page, $order, $desc));
	$tpl->assign('total', $membres->countByCategory($cat_id));

	$cat_id = is_array($cat_id) ? 0 : $cat_id;

	$tpl->assign('pagination_url', Utils::getSelfUrl([
		'p' => '[ID]',
		'o' => $order,
		($desc ? 'd' : 'a') => '',
		'cat' => $cat_id,
	]));

	$tpl->assign('membres_cats', $membres_cats);
	$tpl->assign('membres_cats_cachees', $membres_cats_cachees);
	$tpl->assign('current_cat', $cat_id);

	$tpl->assign('page', $page);
	$tpl->assign('bypage', Membres::ITEMS_PER_PAGE);

}

$tpl->assign('sent', null !== qg('sent'));

$tpl->display('admin/membres/index.tpl');
