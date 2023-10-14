<?php
namespace Paheko;
use Paheko\Services\Fees;

require_once __DIR__ . '/../_inc.php';

$session->requireAccess($session::SECTION_USERS, $session::ACCESS_READ);

$fee = Fees::get((int) qg('id'));

if (!$fee) {
	throw new UserException("Ce tarif n'existe pas");
}

$type = qg('type');
$include_hidden_categories = $session->canAccess($session::SECTION_USERS, $session::ACCESS_ADMIN) && qg('hidden');

if ('unpaid' == $type) {
	$list = $fee->unpaidUsersList($include_hidden_categories);
}
elseif ('expired' == $type) {
	$list = $fee->expiredUsersList($include_hidden_categories);
}
elseif ('active' == $type) {
	$list = $fee->activeUsersList($include_hidden_categories);
}
else {
	$type = 'all';
	$list = $fee->allUsersList($include_hidden_categories);
}

$list->loadFromQueryString();

$service = $fee->service();

$tpl->assign(compact('list', 'fee', 'type', 'service', 'include_hidden_categories'));

$tpl->display('services/fees/details.tpl');
