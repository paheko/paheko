<?php

namespace Paheko\Payments;

use Paheko\Entities\Payments\Payment;
use Paheko\Entities\Payments\Provider;
use Paheko\Entities\Users\User;
use Paheko\DynamicList;
use Paheko\Payments\Providers;
use Paheko\Payments\Users as PaymentsUsers;
use KD2\DB\EntityManager;
use Paheko\DB;
use Paheko\Entities\Accounting\Transaction;

class Payments
{
	const CREATION_LOG_LABEL = 'Paiement créé.';

	static public function createPayment(string $type, string $method, string $status, string $provider_name, ?array $accounts, ?int $author_id, ?int $payer_id, ?string $payer_name, ?string $reference, string $label, int $amount, ?array $user_ids = null, ?array $user_notes = null, ?\stdClass $extra_data = null, ?string $transaction_notes = null): ?Payment
	{
		$payment = new Payment();

		if (!array_key_exists($type, Payment::TYPES)) {
			throw new \InvalidArgumentException('Invalid payment type: ' . $type . '. Allowed types are: ' . implode(', ', Payment::TYPES));
		}
		$payment->set('type', $type);

		if (!array_key_exists($method, Payment::METHODS)) {
			throw new \InvalidArgumentException('Invalid payment method: ' . $method . '. Allowed methods are: ' . implode(', ', Payment::METHODS));
		}
		$payment->set('method', $method);

		if (!array_key_exists($status, Payment::STATUSES)) {
			throw new \InvalidArgumentException('Invalid payment status: ' . $status . '. Allowed statuses are: ' . implode(', ', Payment::STATUSES));
		}
		$payment->set('status', $status);

		$provider = Providers::getByName($provider_name);
		if (null === $provider || !($provider instanceof Provider)) {
			throw new \InvalidArgumentException('Invalid provider: ' . $provider_name);
		}
		$payment->set('provider', $provider->name);

		if ($author_id) {
			if (!DB::getInstance()->test(User::TABLE, 'id = ?', (int)$author_id)) {
				throw new \RuntimeException('Author (User) not found. ID: '. $author_id);
			}
			$payment->set('id_author', (int)$author_id);
		}
		if ($payer_id) {
			$payer = EntityManager::findOneById(User::class, (int)$payer_id);
			if (!$payer) {
				throw new \RuntimeException('User not found. ID: ' . $payer_id);
			}
			$payment->set('id_payer', $payer->id);
			$payment->set('payer_name', $payer->nom);
		}
		else {
			$payment->set('payer_name', $payer_name);
		}
		$payment->set('reference', $reference);
		$payment->set('label', $label);
		$payment->set('amount', $amount);
		$payment->set('date', new \DateTime());
		$payment->addLog(self::CREATION_LOG_LABEL, $payment->date);
		$payment->set('extra_data', $extra_data);
		$payment->selfCheck();
		
		if (!$payment->save()) {
			throw new \RuntimeException(sprintf('Payment recording failed (provider: %s, ID: %s)', $payment->provider, $payment->reference));
		}
		if ($user_ids) {
			foreach ($user_ids as $id) {
				if (!DB::getInstance()->test(User::TABLE, 'id = ?', (int)$id)) {
					throw new \RuntimeException('Associated user not found. ID: '. $id);
				}
			}
			$payment->bindToUsers($user_ids, $user_notes);
		}
		if ($accounts) {
			$payment->createTransaction($accounts, $user_ids, $transaction_notes);
		}
		return $payment;
	}

	static public function getByReference(string $provider_name, string $reference): ?Payment
	{
		return EntityManager::findOne(Payment::class, 'SELECT * FROM @TABLE WHERE provider = :provider AND reference = :reference', $provider_name, $reference);
	}

	static public function list(?string $provider = null): DynamicList
	{
		$columns = [
			'id' => [
				'select' => 'p.id'
			],
			'reference' => [
				'label' => 'Réf.',
				'select' => 'p.reference'
			],
			'users' => [
				'label' => 'Membres',
				'select' => sprintf('(SELECT GROUP_CONCAT(pu.id_user, \';\') FROM %s pu WHERE pu.id_payment = p.id)', PaymentsUsers::TABLE)
			],
			'id_payer' => [
				'select' => 'p.id_payer'
			],
			'payer_name' => [
				'label' => 'Payeur',
				'select' => 'p.payer_name'
			],
			'provider' => [
				'label' => 'Prestataire',
				'select' => 'p.provider'
			],
			'provider_label' => [
				'select' => 'pr.label'
			],
			'type' => [
				'label' => 'Type',
				'select' => 'p.type'
			],
			'status' => [
				'label' => 'Statut',
				'select' => 'p.status'
			],
			'label' => [
				'label' => 'Objet',
				'select' => 'p.label'
			],
			'amount' => [
				'label' => 'Montant',
				'select' => 'p.amount'
			],
			'date' => [
				'label' => 'Date',
				'select' => 'p.date'
			],
			'method' => [
				'label' => 'Méthode',
				'select' => 'p.method'
			],
			'transactions' => [
				'label' => 'Écritures',
				'select' => sprintf('(SELECT GROUP_CONCAT(t.id, \';\') FROM %s t WHERE t.id_payment = p.id)', Transaction::TABLE)
			],
		];

		$tables = Payment::TABLE . ' p ' . ($provider ? 'INDEXED BY payments_provider_date' : '') . '
			LEFT JOIN ' . Provider::TABLE . ' pr ON (pr.name = p.provider)
		';

		$list = new DynamicList($columns, $tables);

		if ($provider) {
			if ($provider !== Providers::MANUAL_PROVIDER && !DB::getInstance()->test(Provider::TABLE, 'name = ?', $provider)) {
				throw new \UnexpectedValueException(sprintf('Invalid provider: %s.', $provider));
			}
			$list->setConditions('provider = :provider_name');
			$list->setParameter('provider_name', $provider);
			$list->setTitle(sprintf('Prestataire - %s - Paiements', $provider));
		}

		$list->setModifier(function ($row) {
			$row->status = Payment::STATUSES[$row->status] ?? 'Inconnu';
			$row->type = Payment::TYPES[$row->type] ?? 'Inconnu';
			$row->method = Payment::METHODS[$row->method] ?? 'Inconnu';
			if ($row->provider === Providers::MANUAL_PROVIDER) {
				$row->provider_label = Providers::MANUAL_PROVIDER_LABEL;
			}
			if (isset($row->users)) {
				$row->users = explode(';', $row->users);
			}
			if (isset($row->transactions)) {
				$row->transactions = explode(';', $row->transactions);
			}
		});

		$list->orderBy('date', true);
		return $list;
	}
}
