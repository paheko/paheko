<?php

namespace Garradin;

use Garradin\Payments\Payments;
use Garradin\Payments\Providers;
use Garradin\Entities\Payments\Payment;
use Garradin\Entities\Users\User;

use KD2\DB\EntityManager;

require_once __DIR__ . '/_inc.php';

$tpl->assign('custom_css', ['!web/css.php']);

$providers = Providers::getAll();
$provider_options = [];
foreach ($providers as $provider) {
	$provider_options[$provider->name] = $provider->label;
}
$tpl->assign('providers', $providers);
$tpl->assign('provider_options', $provider_options);
$tpl->assign('payments', EntityManager::getInstance(Payment::class)->all('SELECT * FROM @TABLE'));

$payment = new Payment();
$form->runIf('save', function () use ($payment) {
	// ToDo: add a nice form check
	Payments::createPayment($_POST['type'], $_POST['method'], Payment::AWAITING_STATUS, $_POST['provider'], array_keys($_POST['author'])[0], null, $_POST['reference'], $_POST['label'], $_POST['amount'] * 100);
	Utils::redirect('!payments.php?ok=1');
});

$tpl->display('payments.tpl');
flush();
