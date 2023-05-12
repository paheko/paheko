<?php

namespace Garradin;

use Garradin\Payments\Payments;
use Garradin\Payments\Providers;
use Garradin\Entities\Payments\Payment;
use Garradin\Entities\Users\User;

use KD2\DB\EntityManager;
use Garradin\UserException;

require_once __DIR__ . '/_inc.php';

$tpl->assign('custom_css', ['!web/css.php']);

if (array_key_exists('id', $_GET)) {
	$payment = EntityManager::findOneById(Payment::class, (int)$_GET['id']);
	if (!$payment) {
		throw new UserException(sprintf('Paiement introuvable : %d', $_GET['id']));
	}
	$provider = Providers::getByName($payment->provider);
	if (!$payment) {
		throw new UserException(sprintf('Paiement introuvable : %d', $_GET['id']));
	}
	$author = EntityManager::findOneById(User::class, (int)$payment->id_author);
	$tpl->assign('payment', $payment);
	$tpl->assign('provider', $provider);
	$tpl->assign('author', $author);

	$tpl->display('payment.tpl');
}
else {
	$providers = Providers::getAll();
	$provider_options = [];
	foreach ($providers as $provider) {
		$provider_options[$provider->name] = $provider->label;
	}
	$tpl->assign('providers', $providers);
	$tpl->assign('provider_options', $provider_options);
	$tpl->assign('payments', EntityManager::getInstance(Payment::class)->all('SELECT * FROM @TABLE'));

	// Not yet implemented
	$list = new \stdClass();
	$list->order = null;
	$tpl->assign('list', $list);

	$payment = new Payment();
	$form->runIf('save', function () use ($payment) {
		// ToDo: add a nice form check
		Payments::createPayment($_POST['type'], $_POST['method'], Payment::AWAITING_STATUS, $_POST['provider'], array_keys($_POST['author'])[0], null, (!empty($_POST['reference']) ? $_POST['reference'] : null), $_POST['label'], $_POST['amount'] * 100);
		Utils::redirect('!payments.php?ok=1');
	});

	$tpl->display('payments.tpl');
}
flush();
