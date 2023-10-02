<?php

namespace Paheko\Entities\Services;

use Paheko\DynamicList;
use Paheko\DB;
use Paheko\Entity;
use Paheko\ValidationException;
use Paheko\Users\DynamicFields;

use KD2\DB\EntityManager;

class Reminder extends Entity
{
	const NAME = 'Rappel';

	const TABLE = 'services_reminders';

	protected int $id;
	protected int $id_service;
	protected int $delay;
	protected string $subject;
	protected string $body;

	const DEFAULT_SUBJECT = 'Votre inscription arrive à expiration';
	const DEFAULT_BODY = 'Bonjour {{$identity}},' . "\n\n" .
		'Votre inscription pour « {{$label}} » arrive à échéance dans {{$nb_days}} jours.' . "\n\n" .
		'Merci de nous contacter pour renouveler votre inscription.' . "\n\nCordialement.";

	public function selfCheck(): void
	{
		parent::selfCheck();
		$this->assert($this->id_service, 'Aucun service n\'a été indiqué pour ce tarif.');
		$this->assert(trim($this->subject) !== '', 'Le sujet doit être renseigné');
		$this->assert(strlen($this->subject) <= 200, 'Le sujet doit faire moins de 200 caractères');
		$this->assert(trim($this->body) !== '', 'Le corps du message doit être renseigné');
		$this->assert(strlen($this->body) <= 64000, 'Le corps du message doit faire moins de 64.000 caractères');
		$this->assert($this->delay !== null, 'Le délai de rappel doit être renseigné');
	}

	public function service()
	{
		return EntityManager::findOneById(Service::class, $this->id_service);
	}

	public function importForm(array $source = null)
	{
		if (null === $source) {
			$source = $_POST;
		}


		if (isset($source['delay_type'])) {
			if (1 == $source['delay_type'] && !empty($source['delay_before'])) {
				$source['delay'] = (int)$source['delay_before'] * -1;
			}
			elseif (2 == $source['delay_type'] && !empty($source['delay_after'])) {
				$source['delay'] = (int)$source['delay_after'];
			}
			else {
				$source['delay'] = 0;
			}
		}

		parent::importForm($source);
	}

	public function sentList(): DynamicList
	{
		$id_field = DynamicFields::getNameFieldsSQL('u');
		$email_field = DynamicFields::getFirstEmailField();
		$db = DB::getInstance();

		$columns = [
			'id_user' => [
				'select' => 'srs.id_user',
			],
			'identity' => [
				'label' => 'Membre',
				'select' => $id_field,
			],
			'email' => [
				'label' => 'Adresse e-mail',
				'select' => 'u.' . $db->quoteIdentifier($email_field),
			],
			'date' => [
				'label' => 'Date d\'envoi',
				'select' => 'srs.sent_date',
				'order' => 'srs.sent_date %s, srs.id %1$s',
			],
		];

		$tables = 'services_reminders_sent srs
			INNER JOIN users u ON u.id = srs.id_user';
		$conditions = sprintf('srs.id_reminder = %d', $this->id());

		$list = new DynamicList($columns, $tables, $conditions);
		$list->orderBy('date', true);
		return $list;
	}

}
