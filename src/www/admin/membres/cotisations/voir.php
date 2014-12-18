<?php
namespace Garradin;

require_once __DIR__ . '/../../_inc.php';

if ($user['droits']['membres'] < Membres::DROIT_ECRITURE)
{
    throw new UserException("Vous n'avez pas le droit d'accéder à cette page.");
}

if (empty($_GET['id']) || !is_numeric($_GET['id']))
{
    throw new UserException("Argument du numéro de cotisation manquant.");
}

$id = (int) $_GET['id'];

$cotisations = new Cotisations;
$m_cotisations = new Membres\Cotisations;

$co = $cotisations->get($id);

if (!$co)
{
    throw new UserException("Cette cotisation n'existe pas.");
}

$page = (int) Utils::get('p') ?: 1;

$tpl->assign('page', $page);
$tpl->assign('bypage', Membres\Cotisations::ITEMS_PER_PAGE);
$tpl->assign('total', $m_cotisations->countMembersForCotisation($co['id']));
$tpl->assign('pagination_url', Utils::getSelfUrl(true) . '?id=' . $co['id'] . '&amp;p=[ID]');

$tpl->assign('cotisation', $co);
$tpl->assign('order', Utils::get('o') ?: 'date');
$tpl->assign('desc', !isset($_GET['a']));
$tpl->assign('liste', $m_cotisations->listMembersForCotisation(
	$co['id'], $page, Utils::get('o'), isset($_GET['a']) ? false : true));

$tpl->display('admin/membres/cotisations/voir.tpl');
