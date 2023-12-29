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
$has_subscriptions = $fee->hasSubscriptions();

$form->runIf('delete', function () use ($has_subscriptions, $fee) {
	if ($has_subscriptions && 0 !== strnatcasecmp($fee->label, trim((string) f('confirm_delete')))) {
		throw new UserException('Merci de recopier le nom du tarif correctement pour confirmer la suppression.');
	}

	$fee->delete();
}, $csrf_key, ADMIN_URL . 'services/fees/?id=' . $fee->id_service);


$confirm_label = null;
$confirm_text = null;

if ($has_subscriptions) {
	$confirm_label = "Entrer ici le nom du tarif pour confirmer que vous souhaitez désinscrire tous les membres de ce tarif";
	$confirm_text = $fee->label;
}

$tpl->assign(compact('fee', 'csrf_key', 'confirm_label', 'confirm_text'));

$tpl->display('services/fees/delete.tpl');
