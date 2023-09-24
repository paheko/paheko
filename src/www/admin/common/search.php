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

Session::getInstance()->requireAccess($access_section, Session::ACCESS_READ);

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
	$s->content = json_encode($s->getAdvancedSearch()->simple($text_query, true));
	$s->type = SE::TYPE_JSON;
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

$is_admin = $session->canAccess($access_section, $session::ACCESS_ADMIN);
$schema = $s->schema();
$columns = $s->getAdvancedSearch()->columns();
$columns = array_filter($columns, fn($c) => $c['label'] ?? null && $c['type'] ?? null); // remove columns only for dynamiclist

$tpl->assign(compact('s', 'list', 'header', 'results', 'columns', 'count', 'is_admin', 'schema'));

if ($s->target == $s::TARGET_ACCOUNTING) {
	$tpl->display('acc/search.tpl');
}
else {
	$tpl->display('users/search.tpl');
}
