<?php
namespace Garradin;
use Garradin\Services\Fees;

require_once __DIR__ . '/../_inc.php';

$session->requireAccess('membres', Membres::DROIT_ADMIN);

$fee = Fees::get((int) qg('id'));

if (!$fee) {
	throw new UserException("Ce tarif n'existe pas");
}

$type = qg('type');

if ('unpaid' == $type) {
	$list = $fee->unpaidUsersList();
}
elseif ('expired' == $type) {
	$list = $fee->expiredUsersList();
}
else {
	$type = 'paid';
	$list = $fee->paidUsersList();
}

$list->loadFromQueryString();

$service = $fee->service();

$tpl->assign(compact('list', 'fee', 'type', 'service'));

$tpl->display('services/fees/details.tpl');
