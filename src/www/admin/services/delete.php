<?php
namespace Paheko;

use Paheko\Services\Services;

require_once __DIR__ . '/_inc.php';

$session->requireAccess($session::SECTION_USERS, $session::ACCESS_ADMIN);

$service = Services::get((int) qg('id'));

if (!$service) {
	throw new UserException("Cette activité n'existe pas");
}

$csrf_key = 'service_delete_' . $service->id();
$has_subscriptions = $service->hasSubscriptions();

$form->runIf('delete', function () use ($service, $has_subscriptions) {
	if ($has_subscriptions && 0 !== strnatcasecmp($service->label, trim((string) f('confirm_delete')))) {
		throw new UserException('Merci de recopier le nom de l\'activité correctement pour confirmer la suppression.');
	}

	$service->delete();
}, $csrf_key, ADMIN_URL . 'services/');

$confirm_label = null;
$confirm_text = null;

if ($has_subscriptions) {
	$confirm_label = "Entrer ici le nom de l'activité pour confirmer que vous souhaitez désinscrire tous les membres de cette activité";
	$confirm_text = $service->label;
}

$tpl->assign(compact('service', 'csrf_key', 'confirm_label', 'confirm_text'));

$tpl->display('services/delete.tpl');
