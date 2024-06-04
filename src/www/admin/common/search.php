<?php
namespace Paheko;

use Paheko\Entities\Search as SE;
use Paheko\Search;
use Paheko\Users\Session;

require_once __DIR__ . '/../_inc.php';

if (!defined('Paheko\CURRENT_SEARCH_TARGET') || !array_key_exists(CURRENT_SEARCH_TARGET, SE::TARGETS)) {
	throw new UserException('Cible inconnue');
}

$access_section = CURRENT_SEARCH_TARGET == SE::TARGET_ACCOUNTING ? $session::SECTION_ACCOUNTING : $session::SECTION_USERS;

$session = Session::getInstance();
$session->requireAccess($access_section, Session::ACCESS_READ);

$is_admin = $session->canAccess($access_section, Session::ACCESS_ADMIN);
$can_sql_unprotected = $session->canAccess(Session::SECTION_CONFIG, Session::ACCESS_ADMIN);

if ($access_section === $session::SECTION_USERS) {
	// Only admins of user section can do custom SQL queries
	// to protect access-restricted user fields from being read
	$can_sql = $is_admin;
}
else {
	// anyone can do custom SQL queries in accounting
	$can_sql = true;
}

$id = f('id') ?: qg('id');

if ($id) {
	$s = Search::get($id);

	if (!$s) {
		throw new UserException('Recherche inconnue ou invalide');
	}
}
else {
	$s = new SE;
	$s->target = CURRENT_SEARCH_TARGET;
	$s->created = new \DateTime();
}

$text_query = trim((string) qg('qt'));
$sql_query = trim((string) f('sql'));
$json_query = f('q') ? json_decode(f('q'), true) : null;
$default = false;

if ($text_query !== '') {
	$options = ['id_year' => qg('year')];

	if ($s->redirect($text_query, $options)) {
		return;
	}

	$s->simple($text_query, $options);

	if ($s->redirectIfSingleResult()) {
		return;
	}
}
elseif ($sql_query !== '') {
	// Only admins can run custom queries, others can only run saved queries
	$session->requireAccess($access_section, $session::ACCESS_ADMIN);

	if (Session::getInstance()->canAccess($access_section, Session::ACCESS_ADMIN) && f('unprotected')) {
		$s->type = SE::TYPE_SQL_UNPROTECTED;
	}
	else {
		$s->type = SE::TYPE_SQL;
	}

	$s->content = $sql_query;
}
elseif ($json_query !== null) {
	$s->content = json_encode(['groups' => $json_query]);
	$s->type = SE::TYPE_JSON;
}
elseif (!$s->content) {
	$s->content = json_encode($s->getAdvancedSearch()->defaults());
	$s->type = SE::TYPE_JSON;
	$default = true;
}

if (f('to_sql')) {
	$s->transformToSQL();
}

$form->runIf(f('save') || f('save_new'), function () use ($s) {
	if (f('save_new') || !$s->exists()) {
		$s = clone $s;
		$label = $s->type != $s::TYPE_JSON ? 'Recherche SQL du ' : 'Recherche avancée du ';
		$label .= date('d/m/Y à H:i');
		$s->label = $label;
	}

	$s->save();

	$target = $s->target == $s::TARGET_ACCOUNTING ? 'acc' : 'users';
	Utils::redirect(sprintf('!%s/saved_searches.php?edit=%d', $target, $s->id()));
});

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

if ($s->exists()) {
	$title = $s->label;
}
elseif (CURRENT_SEARCH_TARGET === SE::TARGET_USERS) {
	$title = 'Recherche de membre';
}
else {
	$title = 'Recherche dans la comptabilité';
}

$tpl->assign(compact('s', 'list', 'header', 'results', 'columns', 'count', 'is_admin', 'schema', 'can_sql', 'can_sql_unprotected', 'title'));

if ($s->target == $s::TARGET_ACCOUNTING) {
	$tpl->display('acc/search.tpl');
}
else {
	$tpl->display('users/search.tpl');
}
