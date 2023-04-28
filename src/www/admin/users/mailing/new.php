<?php
namespace Garradin;

use Garradin\Users\Categories;
use Garradin\Users\Session;
use Garradin\Search;
use Garradin\Services\Services;
use Garradin\Entities\Search as SearchEntity;
use Garradin\Email\Mailings;

require_once __DIR__ . '/_inc.php';

$csrf_key = 'create_mailing';

$target = f('target');

$form->runIf($target == 'all' || f('step3'), function () {
	$m = Mailings::create(f('subject'), f('target'), f('target_id'));
	Utils::redirectDialog('!users/mailing/write.php?id=' . $m->id());
}, $csrf_key);

if ($target == 'category') {
	$tpl->assign('categories', Categories::listWithStats(Categories::WITHOUT_HIDDEN));
}
elseif ($target == 'service') {
	$tpl->assign('services', Services::listWithStats(true));
}
elseif ($target == 'search') {
	$search_list = Search::list(SearchEntity::TARGET_USERS, Session::getUserId());
	$tpl->assign('search_list', array_filter($search_list, fn($s) => $s->hasUserId()));
}

$tpl->assign(compact('csrf_key', 'target'));

$tpl->display('users/mailing/new.tpl');
