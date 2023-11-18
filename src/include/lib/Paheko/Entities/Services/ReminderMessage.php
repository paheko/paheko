<?php

namespace Paheko\Entities\Services;

use Paheko\Plugins;
use Paheko\Entity;
use Paheko\Users\DynamicFields;
use Paheko\UserTemplate\UserTemplate;
use Paheko\UserTemplate\CommonModifiers;

use Paheko\Services\Reminders;
use Paheko\Services\Services;
use Paheko\Services\Fees;
use Paheko\Users\Users;
use Paheko\Email\Emails;

use KD2\DB\Date;
use stdClass;

class ReminderMessage extends Entity
{
	const TABLE = 'services_reminders_sent';

	protected ?int $id;
	protected int $id_service;
	protected int $id_user;
	protected int $id_reminder;
	protected Date $sent_date;
	protected Date $due_date;

	protected ?Reminder $_reminder = null;

	/**
	 * @return UserTemplate|string
	 */
	public function getBody(stdClass $reminder)
	{
		$body = $reminder->body ?? $this->reminder()->body;

		if (false !== strpos($body, '{{')) {
			$body = '{{**keep_whitespaces**}}' . $body;
			return UserTemplate::createFromUserString($body);
		}
		else {
			return $this->body;
		}
	}

	public function getMessage(stdClass $reminder): string
	{
		$body = $this->getBody($reminder);

		$r = self::getMessageVariables($reminder);

		if ($body instanceof UserTemplate) {
			$body->assignArray($r, null, false);

			try {
				$body = $body->fetch();
			}
			catch (\KD2\Brindille_Exception $e) {
				throw new UserException('Erreur de syntaxe dans le corps du message :' . PHP_EOL . $e->getPrevious()->getMessage(), 0, $e);
			}
		}

		return $body;
	}

	static public function getMessageVariables(stdClass $reminder): array
	{
		$reminder = Fees::addUserAmountToObject($reminder, $reminder->id_user);
		$reminder->user_amount = CommonModifiers::money_currency($reminder->user_amount ?? 0, true, false, false);
		$reminder->reminder_date = CommonModifiers::date_short($reminder->reminder_date);
		$reminder->expiry_date = CommonModifiers::date_short($reminder->expiry_date);

		return (array) $reminder;
	}

	public function reminder(): Reminder
	{
		$this->_reminder ??= Reminders::get($this->id_reminder);
		return $this->_reminder;
	}

	public function send(stdClass $reminder)
	{
		$body = $this->getBody($reminder);
		$data = ['data' => (array)$reminder];

		foreach (DynamicFields::getEmailFields() as $email_field) {
			$email = $reminder->$email_field ?? null;

			if (empty($email)) {
				continue;
			}

			$data['data']['email'] = $email;

			// Envoi du mail
			Emails::queue(Emails::CONTEXT_PRIVATE, [$email => $data], null, $reminder->subject, $body);
		}

		$this->save();

		Plugins::fire('reminder.send.after', false, compact('reminder'));
	}

}
