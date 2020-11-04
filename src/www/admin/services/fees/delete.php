<?php
namespace Garradin;

use Garradin\Services\Fees;

require_once __DIR__ . '/../_inc.php';

$session->requireAccess('membres', Membres::DROIT_ADMIN);

$fee = Fees::get((int) qg('id'));

if (!$fee) {
	throw new UserException("Ce tarif n'existe pas");
}

$csrf_key = 'fee_delete_' . $fee->id();

$form->runIf('delete', function () use ($fee) {
	$fee->delete();
}, $csrf_key, ADMIN_URL . 'services/fees/?id=' . $fee->id_service);

$tpl->assign(compact('fee', 'csrf_key'));

$tpl->display('services/fees/delete.tpl');
