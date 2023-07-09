<?php

namespace Garradin;

use Garradin\Payments\Payments;
use Garradin\Payments\Providers;
use Garradin\Entities\Payments\Payment;
use Garradin\Entities\Users\User;
use Garradin\Accounting\Years;

use KD2\DB\EntityManager;
use Garradin\UserException;

require_once __DIR__ . '/../_inc.php';

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
	$tpl->assign([
		'payment' => $payment,
		'provider' => $provider,
		'types' => Payment::TYPES,
		'methods' => Payment::METHODS,
		'author' => $author,
		'TECH_DETAILS' => SHOW_ERRORS && ENABLE_TECH_DETAILS
	]);

	$tpl->display('payments/payment.tpl');
}
else {
	$tpl->assign('payments', Payments::list());

	$tpl->display('payments/payments.tpl');
}
