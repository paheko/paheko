<?php

namespace Garradin\Payments;

use Garradin\Entities\Payments\Payment;
use Garradin\Entities\Payments\Provider;
use Garradin\Entities\Users\User;
use Garradin\Payments\Providers;
use KD2\DB\EntityManager;

class Payments
{
	const CREATION_LOG_LABEL = 'Paiement créé';

	static public function createPayment(string $type, string $method, string $status, string $provider_name, int $author_id, ?string $author_name, string $reference, string $label, int $amount, ?string $extra_data = null): ?Payment
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
		$payment->history = $payment->date->format('Y-m-d H:i:s') . ' - '. self::CREATION_LOG_LABEL;
		$payment->extra_data = $extra_data;
		
		if (!$payment->save())
			return false;
		return $payment;
	}
}
