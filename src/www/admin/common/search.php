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

	if ($s->id_user && $session->user()->id() !== $s->id_user) {
		throw new UserException('Vous n\'avez pas accès à cette recherche');
	}
}
else {
	$s = Search::create(CURRENT_SEARCH_TARGET);
}

$default = $s->populate($session);

$list = $results = $header = $count = null;

if (!$default) {
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
$columns = array_filter($columns, fn($c) => !($c['restricted'] ?? false)); // remove restricted columns from list

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

$operators = $s->getAdvancedSearch()::OPERATORS;

$tpl->assign(compact('s', 'list', 'header', 'results', 'columns', 'count',
	'operators',
	'schema', 'title', 'save_action_url'));

$tpl->display($template_path);
