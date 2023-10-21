<?php
namespace Paheko;

use Paheko\Users\Categories;
use Paheko\Users\Session;
use Paheko\Search;
use Paheko\UserException;
use Paheko\Services\Services;
use Paheko\Entities\Search as SearchEntity;
use Paheko\Email\Mailings;

require_once __DIR__ . '/_inc.php';

$csrf_key = 'create_mailing';

$target = f('target');

$form->runIf($target == 'all' || f('step3'), function () {
	$target = f('target');
	$target_id = f('target_id');

	if ($target !== 'all' && empty($target_id)) {
		throw new UserException('Aucune cible n\'a été sélectionnée.');
	}

	$m = Mailings::create(f('subject'), $target, $target_id);
	Utils::redirectDialog('!users/mailing/write.php?id=' . $m->id());
}, $csrf_key);

if ($target == 'field') {
	$tpl->assign('fields', Users::listCheckboxFieldsTargets());
}
elseif ($target == 'category') {
	$tpl->assign('categories', Categories::listWithStats(Categories::WITHOUT_HIDDEN));
}
elseif ($target == 'service') {
	$tpl->assign('services', Services::listWithStats(true));
}
elseif ($target == 'search') {
	$search_list = Search::list(SearchEntity::TARGET_USERS, Session::getUserId());
	$search_list = array_filter($search_list, fn($s) => $s->hasUserId());
	array_walk($search_list, function (&$s) {
		$s = (object) ['label' => $s->label, 'id' => $s->id, 'count' => $s->countResults()];
	});

	$tpl->assign(compact('search_list'));
}

$tpl->assign(compact('csrf_key', 'target'));

$tpl->display('users/mailing/new.tpl');
