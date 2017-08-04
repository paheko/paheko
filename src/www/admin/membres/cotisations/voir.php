<?php
namespace Garradin;

require_once __DIR__ . '/../_inc.php';

$session->requireAccess('membres', Membres::DROIT_ECRITURE);

qv(['id' => 'required|numeric']);

$id = (int) qg('id');

$cotisations = new Cotisations;
$m_cotisations = new Membres\Cotisations;

$co = $cotisations->get($id);

if (!$co)
{
    throw new UserException("Cette cotisation n'existe pas.");
}

$page = (int) qg('p') ?: 1;

$tpl->assign('page', $page);
$tpl->assign('bypage', Membres\Cotisations::ITEMS_PER_PAGE);
$tpl->assign('total', $m_cotisations->countMembersForCotisation($co->id));
$tpl->assign('pagination_url', Utils::getSelfUrl([
	'id' => $co->id,
	'o' => qg('o'),
	(qg('a') !== null ? 'a' : 'd') => '',
	'p'  => '[ID]',
]));

$tpl->assign('cotisation', $co);
$tpl->assign('order', qg('o') ?: 'date');
$tpl->assign('desc', !null !== qg('a'));
$tpl->assign('liste', $m_cotisations->listMembersForCotisation(
	$co->id, $page, qg('o'), null !== qg('a') ? false : true));

$tpl->display('admin/membres/cotisations/voir.tpl');
