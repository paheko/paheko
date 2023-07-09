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
		throw new UserException(sprintf('Paiement introuvable : %d.', $_GET['id']));
	}

	$provider = Providers::getByName($payment->provider);
	if (!$provider) {
		throw new RuntimeException(sprintf('Prestataire introuvable : %s.', $payment->provider));
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
	if (array_key_exists('provider', $_GET)) {
		if (!$provider = Providers::getByName($_GET['provider'])) {
			throw new UserException(sprintf('Prestataire introuvable : %s.', $_GET['provider']));
		}
	}
	$tpl->assign([
		'payments' => Payments::list($provider->name ?? null),
		'provider' => $provider ?? null
	]);

	$tpl->display('payments/payments.tpl');
}
