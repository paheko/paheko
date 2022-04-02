<?php
namespace Garradin;

use Garradin\Accounting\Years;
use Garradin\Entities\Search as SE;
use Garradin\Search;
use Garradin\Users\Session;

require_once __DIR__ . '/../_inc.php';

if (!defined('Garradin\CURRENT_SEARCH_TARGET') || !array_key_exists(CURRENT_SEARCH_TARGET, SE::TARGETS)) {
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
}

$text_query = trim((string) qg('qt'));
$sql_query = trim((string) f('sql'));
$json_query = f('q') ? json_decode(f('q'), true) : null;
$csrf_key = 'search_' . CURRENT_SEARCH_TARGET;

if ($text_query !== '') {
	$query = $s->getAdvancedSearch()->simple($text_query);
	$s->type = SE::TYPE_JSON;
}
elseif ($sql_query !== '') {
	if (Session::getInstance()->canAccess($access_section, Session::ACCESS_ADMIN) && f('unprotected')) {
		$s->type = SE::TYPE_SQL_UNPROTECTED;
	}
	else {
		$s->type = SE::TYPE_SQL;
	}
}
elseif ($json_query === null) {
	$json_query = $s->getAdvancedSearch()->defaults();
	$s->type = SE::TYPE_JSON;
}
else {
	$s->type = SE::TYPE_JSON;
}

if ($s->type == SE::TYPE_JSON) {
	$s->content = json_encode($json_query);
}
else {
	$s->content = $sql_query;
}

// Recherche SQL
if ($sql_query !== '') {
	// Only admins can run custom queries, others can only run saved queries
	$session->requireAccess($access_section, $session::ACCESS_ADMIN);
}

$form->runIf(f('save') || f('save_new'), function () use ($s) {
	if (f('save_new') || !$s->exists()) {
		$s = clone $s;
		$label = $s->type != $s::TYPE_JSON ? 'Recherche SQL du ' : 'Recherche avancée du ';
		$label .= date('d/m/Y à H:i');
	}

	$s->save();

	$target = $s->target == $s::TARGET_ACCOUNTING ? 'acc' : 'users';
	Utils::redirect(sprintf('%s/saved_searches.php?edit=%d', $target, $s->id()));
}, $csrf_key);

$list = $results = $header = null;

if($s->exists() || !empty($_POST)) {
	if ($s->type == $s::TYPE_JSON) {
		$list = $s->getDynamicList();
		$list->loadFromQueryString();
	}
	else {
		$header = $s->getHeader();
		$results = $s->iterateResults();
	}
}

$is_admin = $session->canAccess($access_section, $session::ACCESS_ADMIN);
$schema = $s->getAdvancedSearch()->schema();
$columns= $s->getAdvancedSearch()->columns();

$tpl->assign(compact('s', 'list', 'header', 'results', 'columns', 'is_admin', 'schema'));

if ($s->target == $s::TARGET_ACCOUNTING) {
	$tpl->display('acc/search.tpl');
}
else {
	$tpl->display('users/search.tpl');
}
