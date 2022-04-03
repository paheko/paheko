<?php
namespace Garradin;

use Garradin\Entities\Search as SE;
use Garradin\Search;
use Garradin\Users\Session;

require_once __DIR__ . '/../_inc.php';

if (!defined('Garradin\CURRENT_SEARCH_TARGET') || !array_key_exists(CURRENT_SEARCH_TARGET, SE::TARGETS)) {
	throw new UserException('Cible inconnue');
}

if (empty($search_url)) {
	throw new \LogicException('Missing $search_url');
}

$access_section = CURRENT_SEARCH_TARGET == 'accounting' ? $session::SECTION_ACCOUNTING : $session::SECTION_USERS;

$mode = null;

if (qg('edit') || qg('delete'))
{
	$s = Search::get(qg('edit') ?: qg('delete'));

	if (!$s) {
		throw new UserException('Recherche non trouvée');
	}

	if ($s->id_user !== null && $r->id_user != Session::getInstance()->getUser()->id) {
		throw new UserException('Recherche privée appartenant à un autre membre.');
	}

	$csrf_key = 'search_' . $s->id;

	$form->runIf('save', function () use ($s) {
		$s->importForm();
		$s->save();
	}, $csrf_key, Utils::getSelfURI(false));

	$form->runIf('delete', function () use ($s) {
		$s->delete();
	}, $csrf_key, Utils::getSelfURI(false));

	$tpl->assign('search', $s);
	$tpl->assign('csrf_key', $csrf_key);

	$mode = qg('edit') ? 'edit' : 'delete';
}
else {
	$tpl->assign('list', Search::list(Session::getInstance()->getUser()->id, CURRENT_SEARCH_TARGET));
	$mode = 'list';
}

$target = CURRENT_SEARCH_TARGET;
$tpl->assign(compact('mode', 'target', 'search_url', 'access_section'));

$tpl->display('common/search/saved_searches.tpl');
