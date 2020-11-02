<?php
namespace Garradin;

require_once __DIR__ . '/../_inc.php';

use Garradin\Services\Services;

require_once __DIR__ . '/_inc.php';

$session->requireAccess('membres', Membres::DROIT_ADMIN);

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
else {
	$type = 'all';
	$list = $service->distinctUsersList();
}

$list->loadFromQueryString();

$tpl->assign(compact('list', 'service', 'type'));

$tpl->display('services/details.tpl');
