<?php
namespace Paheko;
use Paheko\Services\Services;

require_once __DIR__ . '/_inc.php';

$session->requireAccess($session::SECTION_USERS, $session::ACCESS_READ);

$service = Services::get((int) qg('id'));

if (!$service) {
	throw new UserException("Cette activité n'existe pas");
}

$type = qg('type');
$include_hidden_categories = $session->canAccess($session::SECTION_USERS, $session::ACCESS_ADMIN) && qg('hidden');

if ('unpaid' == $type) {
	$list = $service->unpaidUsersList($include_hidden_categories);
}
elseif ('expired' == $type) {
	$list = $service->expiredUsersList($include_hidden_categories);
}
elseif ('active' == $type) {
	$list = $service->activeUsersList($include_hidden_categories);
}
else {
	$type = 'all';
	$list = $service->allUsersList($include_hidden_categories);
}

$list->loadFromQueryString();

$tpl->assign(compact('list', 'service', 'type', 'include_hidden_categories'));

$tpl->display('services/details.tpl');
