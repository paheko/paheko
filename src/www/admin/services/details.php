<?php
namespace Garradin;
use Garradin\Services\Services;

require_once __DIR__ . '/_inc.php';

$session->requireAccess($session::SECTION_USERS, $session::ACCESS_READ);

$service = Services::get((int) qg('id'));

if (!$service) {
	throw new UserException("Cette activité n'existe pas");
}

$type = qg('type');

if ('unpaid' == $type) {
	$list = $service->unpaidUsersList();
}
elseif ('expired' == $type) {
	$list = $service->expiredUsersList();
}
elseif ('active' == $type) {
	$list = $service->activeUsersList();
}
else {
	$type = 'all';
	$list = $service->allUsersList();
}

$list->loadFromQueryString();

$tpl->assign(compact('list', 'service', 'type'));

$tpl->display('services/details.tpl');
