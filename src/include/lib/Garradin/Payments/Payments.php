<?php

namespace Garradin\Payments;

use Garradin\Entities\Payments\Payment;
use Garradin\Entities\Payments\Provider;
use Garradin\Entities\Users\User;
use Garradin\DynamicList;
use Garradin\Payments\Providers;
use KD2\DB\EntityManager;
use Garradin\Entities\Accounting\Transaction;
use Garradin\Accounting\Years;

class Payments
{
	const CREATION_LOG_LABEL = 'Paiement créé';
	const TRANSACTION_CREATION_LOG_LABEL = 'Écriture comptable ajoutée';
	const TRANSACTION_PREFIX = 'Paiement';

	static public function createPayment(string $type, string $method, string $status, string $provider_name, ?array $accounts, ?int $author_id, ?string $author_name, ?string $reference, string $label, int $amount, ?\stdClass $extra_data = null, ?string $transaction_notes = null): ?Payment
	{
		$payment = new Payment();

		if (!array_key_exists($type, Payment::TYPES)) {
			throw new \InvalidArgumentException('Invalid payment type: ' . $type . '. Allowed types are: ' . implode(', ', Payment::TYPES));
		}
		$payment->type = $type;

		if (!array_key_exists($method, Payment::METHODS)) {
			throw new \InvalidArgumentException('Invalid payment method: ' . $method . '. Allowed methods are: ' . implode(', ', Payment::METHODS));
		}
		$payment->method = $method;

		if (!array_key_exists($status, Payment::STATUSES)) {
			throw new \InvalidArgumentException('Invalid payment status: ' . $status . '. Allowed statuses are: ' . implode(', ', Payment::STATUSES));
		}
		$payment->status = $status;

		$provider = Providers::getByName($provider_name);
		if (null === $provider || !($provider instanceof Provider)) {
			throw new \InvalidArgumentException('Invalid provider: ' . $provider_name);
		}
		$payment->provider = $provider->name;

		if ($author_id) {
			$author = EntityManager::findOneById(User::class, (int)$author_id);
			if (!$author) {
				throw new \RuntimeException('User not found. ID: '. $author_id);
			}
			$payment->id_author = $author->id;
			$payment->author_name = $author->nom;
		}
		else {
			$payment->author_name = $author_name;
		}
		$payment->reference = $reference;
		$payment->label = $label;
		$payment->amount = $amount;
		$payment->date = new \DateTime();
		$payment->addLog(self::CREATION_LOG_LABEL, $payment->date);
		$payment->set('extra_data', $extra_data);
		
		if (!$payment->save()) {
			throw new \RuntimeException(sprintf('Payment recording failed (provider: %s, ID: %s)', $payment->provider, $payment->reference));
		}
		if ($accounts) {
			$transaction = self::createTransaction($payment, $accounts, $transaction_notes);
			$payment->set('id_transaction', (int)$transaction->id);
			$payment->addLog(self::TRANSACTION_CREATION_LOG_LABEL);
			$payment->save();
		}
		return $payment;
	}

	static public function createTransaction(Payment $payment, array $accounts, ?string $notes = null): Transaction
	{
		if (!$id_year = Years::getOpenYearIdMatchingDate($payment->date)) {
			throw new \RuntimeException(sprintf('No opened accounting year matching the payment date "%s"!', $payment->date->format('Y-m-d')));
		}
		// ToDo: check accounts validity (right number for the Transaction type)

		$transaction = new Transaction();
		$transaction->type = Transaction::TYPE_REVENUE;

		$source = [
			'status' => Transaction::STATUS_PAID,
			'label' => self::TRANSACTION_PREFIX . ' - ' . $payment->label,
			'notes' => $notes,
			'payment_reference' => $payment->id,
			'date' => \KD2\DB\Date::createFromInterface($payment->date),
			'id_year' => (int)$id_year,
			'amount' => $payment->amount / 100,
			'simple' => [
				Transaction::TYPE_REVENUE => [
					'credit' => [ (int)$accounts[0] => null ],
					'debit' => [ (int)$accounts[1] => null ]
			]]
			// , 'id_user'/'id_creator' => ...
		];

		$transaction->importForm($source);

		if (!$transaction->save()) {
			throw new \RuntimeException(sprintf('Cannot record payment transaction. Payment ID: %d.', $payment->id));
		}
		return $transaction;
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
			'id_transaction' => [
				'label' => 'Écriture',
				'select' => 'p.id_transaction'
			],
			'id_author' => [
				'select' => 'p.id_author'
			],
			'author_name' => [
				'label' => 'Auteur/trice',
				'select' => 'p.author_name'
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
			]
		];

		$tables = Payment::TABLE . ' p
			LEFT JOIN ' . Provider::TABLE . ' pr ON (pr.name = p.provider)
		';

		$list = new DynamicList($columns, $tables);

		if ($provider) {
			$list->setConditions('provider = :provider_name');
			$list->setParameter('provider_name', $provider);
			$list->setTitle(sprintf('Prestataire - %s - Paiements', $provider));
		}

		$list->setModifier(function ($row) {
			$row->status = Payment::STATUSES[$row->status] ?? 'Inconnu';
			$row->type = Payment::TYPES[$row->type] ?? 'Inconnu';
			$row->method = Payment::METHODS[$row->method] ?? 'Inconnu';
		});

		$list->orderBy('date', true);
		return $list;
	}
}
