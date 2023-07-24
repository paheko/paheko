<?php

namespace Paheko;

use Paheko\Payments\Payments;
use Paheko\Payments\Providers;
use Paheko\Payments\Users as PaymentsUsers;
use Paheko\Entities\Payments\Payment;
use Paheko\Entities\Users\User;
use Paheko\Accounting\Years;

use KD2\DB\EntityManager;
use Paheko\UserException;

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
	$payer = EntityManager::findOneById(User::class, (int)$payment->id_payer);
	$tpl->assign([
		'payment' => $payment,
		'provider' => $provider,
		'transactions' => $payment->getTransactions(),
		'types' => Payment::TYPES,
		'methods' => Payment::METHODS,
		'author' => $author,
		'payer' => $payer,
		'users' => PaymentsUsers::getForPaymentId((int)$payment->id),
		'users_notes' => PaymentsUsers::getNotesForPaymentId((int)$payment->id),
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
	if ($id_user = qg('id_user')) {
		$user = EntityManager::findOneById(User::class, (int)$id_user);
	}
	$tpl->assign([
		'payments' => Payments::list($provider->name ?? null, $user ?? null),
		'provider' => $provider ?? null
	]);

	$tpl->display('payments/payments.tpl');
}
