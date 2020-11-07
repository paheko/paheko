<?php
namespace Garradin;

use Garradin\Services\Services;
use Garradin\Entities\Services\Service_User;
use Garradin\Entities\Accounting\Account;

require_once __DIR__ . '/_inc.php';

$session->requireAccess('membres', Membres::DROIT_ECRITURE);

$user_id = (int) qg('user') ?: null;
$user_name = $user_id ? (new Membres)->getNom($user_id) : null;

$grouped_services = Services::listGroupedWithFees($user_id);

if (!count($grouped_services)) {
	Utils::redirect(ADMIN_URL . 'services/?CREATE');
}

$csrf_key = 'service_save';

$form->runIf('save', function () {
	$su = new Service_User;
	$su->saveFromForm($user->id);

	Utils::redirect(ADMIN_URL . 'services/user.php?id=' . $su->id_user);
}, $csrf_key);

$selected_user = $user_id ? [$user_id => $user_name] : null;

$account_targets = implode(',', [Account::TYPE_BANK, Account::TYPE_CASH, Account::TYPE_OUTSTANDING]);

$tpl->assign(compact('grouped_services', 'csrf_key', 'selected_user', 'account_targets'));

$tpl->display('services/save.tpl');
