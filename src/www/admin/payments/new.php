<?php

namespace Garradin;

use Garradin\Payments\Payments;
use Garradin\Payments\Providers;
use Garradin\Entities\Payments\Payment;
use Garradin\Entities\Payments\Provider;
use Garradin\Entities\Accounting\Year;
use Garradin\Entities\Accounting\Account;
use Garradin\Entities\Users\User;
use Garradin\Accounting\Years;

use KD2\DB\EntityManager;
use Garradin\UserException;

require_once __DIR__ . '/../_inc.php';

$tpl->assign('custom_css', ['!web/css.php']);

$providers = Providers::getAll();
$provider_options = [];
foreach ($providers as $provider) {
	$provider_options[$provider->name] = $provider->label;
}

$csrf_key = 'payment';

$tpl->assign([
	'provider_options' => $provider_options,
	'years' => Years::listOpen(),
	'csrf_key' => $csrf_key
]);

$payment = new Payment();
$form->runIf('save', function () use ($payment) {

	if (!array_key_exists($_POST['type'], Payment::TYPES)) {
		throw new UserException(sprintf('Type invalide : %s.', $_POST['type']));
	}
	if (!array_key_exists($_POST['method'], Payment::METHODS)) {
		throw new UserException(sprintf('Méthode invalide : %s.', $_POST['method']));
	}

	$provider = Providers::getByName($_POST['provider']);
	if (null === $provider || !($provider instanceof Provider)) {
		throw new UserException(sprintf('Prestataire invalide : %s.', $_POST['provider']));
	}

	$author_id = Form::getSelectorValue($_POST['author']);
	$author = EntityManager::findOneById(User::class, (int)$author_id);
	if (!$author) {
		throw new UserException('Auteur/trice n°%d inconnu.e.', $author_id);
	}

	if (array_key_exists('accounting', $_POST)) {
		if (!$year = EntityManager::findOneById(Year::class, $_POST['id_year'])) {
			throw new UserException(sprintf('Exercice introuvable (n°%d).', $_POST['id_year']));
		}

		$id = (int)Form::getSelectorValue($_POST['credit']);
		if (!$credit_account = EntityManager::findOneById(Account::class, $id)) {
			throw new UserException(sprintf('Type de recette "%s" (n°%d) introuvable.', $_POST['credit'][$id], $id));
		}

		$id = (int)Form::getSelectorValue($_POST['debit']);
		if (!$debit_account = EntityManager::findOneById(Account::class, $id)) {
			throw new UserException(sprintf('Compte d\'encaissement "%s" (n°%d) introuvable.', $_POST['debit'][$id], $id));
		}
	}

	$accounts = array_key_exists('accounting', $_POST) ? [ $credit_account->id, $debit_account->id ] : null;
	Payments::createPayment($_POST['type'], $_POST['method'], Payment::AWAITING_STATUS, $_POST['provider'], $accounts, $author->id, null, (!empty($_POST['reference']) ? $_POST['reference'] : null), $_POST['label'], $_POST['amount'] * 100, null, $_POST['notes']);
}, $csrf_key, '!payments/payments.php?ok=1');

$tpl->display('payments/new.tpl');
