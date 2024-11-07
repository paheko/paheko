<?php
namespace Paheko;

use Paheko\Entities\Search as SE;
use Paheko\Search;
use Paheko\Users\Session;

require_once __DIR__ . '/../_inc.php';

if (!defined('Paheko\CURRENT_SEARCH_TARGET') || !array_key_exists(CURRENT_SEARCH_TARGET, SE::TARGETS)) {
	throw new UserException('Cible inconnue');
}

if (empty($search_url)) {
	throw new \LogicException('Missing $search_url');
}

$access_section = CURRENT_SEARCH_TARGET == 'accounting' ? $session::SECTION_ACCOUNTING : $session::SECTION_USERS;

$mode = null;
$search = null;
$csrf_key = 'search';

if (qg('delete')) {
	$search = Search::get(qg('delete'));

	if (!$search) {
		throw new UserException('Recherche non trouvée');
	}

	if ($search->id_user !== null && $search->id_user != Session::getInstance()->getUser()->id) {
		throw new UserException('Recherche privée appartenant à un autre membre.');
	}

	$form->runIf('delete', function () use ($search) {
		$search->delete();
	}, $csrf_key, Utils::getSelfURI(false));

	$mode = 'delete';
}
elseif (qg('edit') !== null) {
	if (qg('edit')) {
		$search = Search::get((int) qg('edit'));

		if (!$search) {
			throw new UserException('Recherche non trouvée');
		}

		if ($search->id_user !== null && $search->id_user != Session::getInstance()->getUser()->id) {
			throw new UserException('Recherche privée appartenant à un autre membre.');
		}
	}
	else {
		$search = Search::create(CURRENT_SEARCH_TARGET, f('type'));
	}

	$search->populate($session);

	$form->runIf('save', function () use ($search) {
		$search->importForm();
		$search->save();
	}, $csrf_key, Utils::getSelfURI(false));

	$form->runIf('duplicate', function () use ($search) {
		$search = clone $search;
		$search->importForm();
		$search->save();
	}, $csrf_key, Utils::getSelfURI(false));

	$mode = 'edit';
}
else {
	$list = Search::getList(CURRENT_SEARCH_TARGET, Session::getUserId());
	$list->loadFromQueryString();
	$tpl->assign(compact('list'));
	$mode = 'list';
}

$target = CURRENT_SEARCH_TARGET;
$target_label = SE::TARGETS[CURRENT_SEARCH_TARGET];
$tpl->assign(compact('mode', 'target', 'target_label', 'search_url', 'access_section', 'search', 'csrf_key'));

$tpl->display('common/search/saved_searches.tpl');
