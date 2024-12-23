<?php
namespace Paheko;

use Paheko\Log;
use Paheko\Users\Session;

require_once __DIR__ . '/../_inc.php';

$params = [];

if ($id = (int)qg('history')) {
	$session->requireAccess($session::SECTION_USERS, $session::ACCESS_ADMIN);
	$params['history'] = $id;
}
elseif (($id = (int)qg('id'))) {
	$session->requireAccess($session::SECTION_USERS, $session::ACCESS_ADMIN);
	$params['id_user'] = $id;
}
else {
	$params['id_self'] = Session::getUserId();

	if (!$params['id_self']) {
		throw new UserException('Access forbidden');
	}
}

$tpl->assign('current', isset($params['id_self']) ? 'me' : 'users');

$list = Log::list($params);
$list->loadFromQueryString();

$tpl->assign(compact('list', 'params'));

$tpl->display('users/log.tpl');
