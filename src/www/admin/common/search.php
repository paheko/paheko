<?php
namespace Paheko;

use Paheko\Entities\Search as SE;
use Paheko\Search;
use Paheko\Users\Session;

require_once __DIR__ . '/../_inc.php';

if (!defined('Paheko\CURRENT_SEARCH_TARGET') || !array_key_exists(CURRENT_SEARCH_TARGET, SE::TARGETS)) {
	throw new UserException('Cible inconnue');
}

$session = Session::getInstance();
$id = f('id') ?: qg('id');

if ($id) {
	$s = Search::get((int) $id);

	if (!$s) {
		throw new UserException('Recherche inconnue ou invalide');
	}
}
else {
	$s = Search::create(CURRENT_SEARCH_TARGET);
}

$p = $s->populate($session);
$tpl->assign($p);

$list = $results = $header = $count = null;

if (!$p['default']) {
	try {
		if ($s->type == $s::TYPE_JSON) {
			$list = $s->getDynamicList();
			$list->loadFromQueryString();
			$count = $list->count();
		}
		else {
			if (!empty($_POST['_export'])) {
				$s->export($_POST['_export']);
				exit;
			}

			$header = $s->getHeader();
			$count = $s->countResults(false);
			$results = $s->iterateResults();
			$tpl->assign('has_limit', $s->hasLimit());
		}
	}
	catch (UserException $e) {
		$form->addError($e->getMessage());
	}
}

$schema = $s->schema();
$columns = $s->getAdvancedSearch()->columns();
$columns = array_filter($columns, fn($c) => $c['label'] ?? null && $c['type'] ?? null); // remove columns only for dynamiclist

if (CURRENT_SEARCH_TARGET === SE::TARGET_USERS) {
	 $save_action_url = '!users/saved_searches.php?edit';
	 $template_path = 'users/search.tpl';
}
else {
	 $save_action_url = '!acc/saved_searches.php?edit';
	 $template_path = 'acc/search.tpl';
}

if ($s->exists()) {
	$save_action_url .= '=' . $s->id();
	$title = $s->label;
}
elseif (CURRENT_SEARCH_TARGET === SE::TARGET_USERS) {
	$title = 'Recherche de membre';
}
else {
	$title = 'Recherche dans la comptabilité';
}

$save_action_url = Utils::getLocalURL($save_action_url);

$tpl->assign(compact('s', 'list', 'header', 'results', 'columns', 'count',
	'schema', 'title', 'save_action_url'));

$tpl->display($template_path);
