<?php
namespace Garradin;

use Garradin\Log;
use Garradin\Users\Session;

require_once __DIR__ . '/../_inc.php';

if (!$session->canAccess($session::SECTION_USERS, $session::ACCESS_READ)) {
	$id = Session::getUserId();

	if (!$id) {
		throw new UserException('');
	}
}
else {
	$id = (int)qg('id') ?: null;
}

$tpl->assign('current', $id == Session::getUserId() ? 'me' : 'users');

$list = Log::list($id);
$list->loadFromQueryString();

$tpl->assign(compact('list', 'id'));

$tpl->display('users/log.tpl');
