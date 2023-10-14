<?php
namespace Paheko;

use Paheko\Services\Fees;

require_once __DIR__ . '/../_inc.php';

$session->requireAccess($session::SECTION_USERS, $session::ACCESS_ADMIN);

$fee = Fees::get((int) qg('id'));

if (!$fee) {
	throw new UserException("Ce tarif n'existe pas");
}

$csrf_key = 'fee_delete_' . $fee->id();

$form->runIf('delete', function () use ($fee) {
	if (!f('confirm_delete')) {
		throw new UserException('Merci de cocher la case pour confirmer la suppression.');
	}

	$fee->delete();
}, $csrf_key, ADMIN_URL . 'services/fees/?id=' . $fee->id_service);

$tpl->assign(compact('fee', 'csrf_key'));

$tpl->display('services/fees/delete.tpl');
